<?php

namespace Drupal\jcc_pdf_upload_validation_checker\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\file\FileInterface;
use Drupal\jcc_pdf_upload_validation_checker\PdfAudit\PdfAuditRunner;
use Drupal\media\MediaInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes PDF validation jobs.
 *
 * @QueueWorker(
 *   id = "jcc_pdf_upload_validation_checker_pdf_audit",
 *   title = @Translation("JCC PDF audit"),
 *   cron = {"time" = 120}
 * )
 */
final class PdfAuditQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  private const MEDIA_FILE_FIELDS = [
    'field_media_file',
    'field_media_file_farsi',
    'field_media_file_arabic',
    'field_media_file_cambodian',
    'field_media_file_chinese_simple',
    'field_media_file_chinese',
    'field_east_armenian_file',
    'field_media_file_hmong',
    'field_media_file_korean',
    'field_media_file_multiple',
    'field_media_file_punjabi',
    'field_media_file_russian',
    'field_media_file_spanish',
    'field_media_file_tagalog',
    'field_media_file_vietnamese',
  ];

  /**
   * Finds medias that reference a given file ID in any known file field.
   *
   * @param int $fid
   *   The file entity ID.
   *
   * @return \Drupal\media\MediaInterface[]
   *   An array of loaded media entities.
   */
  private function loadMediaReferencingFile(int $fid): array {
    $query = $this->entityTypeManager->getStorage('media')->getQuery()
      ->accessCheck(FALSE);

    $or = $query->orConditionGroup();
    foreach (self::MEDIA_FILE_FIELDS as $field_name) {
      $or->condition($field_name, $fid);
    }

    $mids = $query->condition($or)->execute();
    if (empty($mids)) {
      return [];
    }

    /** @var \Drupal\media\MediaInterface[] $media */
    $media = $this->entityTypeManager->getStorage('media')->loadMultiple($mids);
    return array_values(array_filter($media, fn($m) => $m instanceof MediaInterface));
  }

  /**
   * Returns TRUE if any file referenced by the media has audit status "fail".
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity to check.
   *
   * @return bool
   *   TRUE if any referenced file failed audit, otherwise FALSE.
   */
  private function mediaHasAnyFailedFile(MediaInterface $media): bool {
    foreach (self::MEDIA_FILE_FIELDS as $field_name) {
      if (!$media->hasField($field_name) || $media->get($field_name)->isEmpty()) {
        continue;
      }

      foreach ($media->get($field_name) as $item) {
        $file = $item->entity;
        if (!$file instanceof FileInterface) {
          continue;
        }

        if ($file->getMimeType() !== 'application/pdf') {
          continue;
        }

        if ($file->hasField('field_pdf_audit_status')) {
          $status = (string) $file->get('field_pdf_audit_status')->value;
          if ($status === 'fail') {
            return TRUE;
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * Publishes a media entity if it is not already published.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   */
  private function publishMediaIfNeeded(MediaInterface $media): void {
    $storage = \Drupal::entityTypeManager()->getStorage('media');
    $media = $storage->load($media->id());

    if (!$media) {
      return;
    }

    $is_published = method_exists($media, 'isPublished')
      ? (bool) $media->isPublished()
      : ((int) $media->get('status')->value === 1);

    if ($is_published) {
      return;
    }

    if (method_exists($media, 'setPublished')) {
      $media->setPublished(TRUE);
    }
    else {
      $media->set('status', 1);
    }

    if ($media->hasField('moderation_state')) {
      $media->set('moderation_state', 'published');
    }

    \Drupal::logger('jcc_pdf_upload_validation_checker')->notice(
      'Saving media @mid with status=@status moderation_state=@state',
      [
        '@mid' => $media->id(),
        '@status' => $media->hasField('status') ? $media->get('status')->value : 'none',
        '@state' => $media->hasField('moderation_state') ? $media->get('moderation_state')->value : 'none',
      ]
    );

    $media->save();
  }

  /**
   * Constructs a new PdfAuditQueueWorker instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private EntityTypeManagerInterface $entityTypeManager,
    private ClientInterface $httpClient,
    private ConfigFactoryInterface $configFactory,
    private PdfAuditRunner $pdfAuditRunner,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('jcc_pdf_upload_validation_checker.pdf_audit_runner'),
    );
  }

  /**
   * Processes a single PDF audit queue item.
   *
   * @param array $data
   *   The queue item data. Expected structure:
   *   - fid: (int) The file entity ID to validate.
   */
  public function processItem($data): void {
    $config = $this->configFactory->get('jcc_pdf_upload_validation_checker.settings');

    $fid = (int) ($data['fid'] ?? 0);
    if (!$fid) {
      return;
    }

    /** @var \Drupal\file\FileInterface|null $file */
    $file = $this->entityTypeManager->getStorage('file')->load($fid);
    if (!$file) {
      return;
    }

    if ($file->getMimeType() !== 'application/pdf') {
      return;
    }

    if (!$file->hasField('field_pdf_audit_status')) {
      return;
    }

    \Drupal::logger('jcc_pdf_upload_validation_checker')->notice('Cron processing PDF validation on fid=@fid', ['@fid' => $fid]);

    // Read file bytes from URI.
    $result = $this->pdfAuditRunner->auditFile($file);

    if (empty($result['passed'])) {
      $file->set('field_pdf_audit_status', 'fail');
      $file->save();

      \Drupal::logger('jcc_pdf_upload_validation_checker')->notice(
        'PDF fid=@fid failed validation',
        ['@fid' => $fid]
      );
      return;
    }

    $file->set('field_pdf_audit_status', 'pass');
    $file->save();

    \Drupal::logger('jcc_pdf_upload_validation_checker')->notice(
      'PDF fid=@fid passed validation',
      ['@fid' => $fid]
    );

    \Drupal::logger('jcc_pdf_upload_validation_checker')->notice('PDF fid=@fid passed validation', ['@fid' => $fid]);

    foreach ($this->loadMediaReferencingFile($fid) as $media) {
      if ($this->mediaHasAnyFailedFile($media)) {
        // At least one failed file exists on this media: keep unpublished.
        continue;
      }
      $this->publishMediaIfNeeded($media);
    }
  }

}
