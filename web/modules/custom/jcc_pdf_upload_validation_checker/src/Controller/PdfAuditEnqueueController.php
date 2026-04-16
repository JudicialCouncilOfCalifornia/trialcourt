<?php

namespace Drupal\jcc_pdf_upload_validation_checker\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Queue\QueueFactory;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for PDF audit enqueue operations.
 *
 * Route: /api/pdf-audit/{fid}/pending.
 */
final class PdfAuditEnqueueController extends ControllerBase {

  /**
   * The queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Constructs a PdfAuditEnqueueController object.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory service.
   */
  public function __construct(QueueFactory $queueFactory) {
    $this->queueFactory = $queueFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue'),
    );
  }

  /**
   * Marks a file's PDF audit status as pending and enqueues it for processing.
   *
   * Also unpublishes any media entities that reference this file.
   *
   * @param int $fid
   *   The file entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response indicating the result of the operation.
   */
  public function markPendingAndQueue(int $fid): JsonResponse {
    /** @var \Drupal\file\FileInterface|null $file */
    $file = $this->entityTypeManager()->getStorage('file')->load($fid);
    if (!$file) {
      throw new NotFoundHttpException("File $fid not found.");
    }

    if ($file->getMimeType() !== 'application/pdf') {
      return new JsonResponse([
        'ok' => FALSE,
        'fid' => $fid,
        'message' => 'Not a PDF.',
      ], 400);
    }

    if (!$file->hasField('field_pdf_audit_status')) {
      return new JsonResponse([
        'ok' => FALSE,
        'fid' => $fid,
        'message' => 'field_pdf_audit_status is not present on file entity.',
      ], 500);
    }

    // Set pending on the file.
    $file->set('field_pdf_audit_status', 'pending');
    if ($file->hasField('field_pdf_audit_report')) {
      $file->set('field_pdf_audit_report', NULL);
    }
    $file->save();

    // Immediately unpublish any referencing media.
    $this->unpublishReferencingMedia($fid);

    // Queue it.
    $queue_name = 'jcc_pdf_upload_validation_checker_pdf_audit';
    $this->queueFactory->get($queue_name)->createItem(['fid' => $fid]);

    return new JsonResponse([
      'ok' => TRUE,
      'fid' => $fid,
      'queued' => $queue_name,
      'status' => 'pending',
    ]);
  }

  /**
   * Unpublishes all media entities that reference the given file.
   *
   * @param int $fid
   *   The file entity ID.
   */
  protected function unpublishReferencingMedia(int $fid): void {
    $media_storage = $this->entityTypeManager()->getStorage('media');

    $file_fields = [
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

    $query = $media_storage->getQuery()->accessCheck(FALSE);
    $or = $query->orConditionGroup();

    foreach ($file_fields as $field_name) {
      $or->condition($field_name . '.target_id', $fid);
    }

    $mids = $query->condition($or)->execute();
    if (!$mids) {
      return;
    }

    /** @var \Drupal\media\MediaInterface[] $media_entities */
    $media_entities = $media_storage->loadMultiple($mids);

    foreach ($media_entities as $media) {
      if (!$media instanceof MediaInterface) {
        continue;
      }

      $changed = FALSE;

      if ((int) $media->get('status')->value !== 0) {
        $media->set('status', 0);
        $changed = TRUE;
      }

      if (method_exists($media, 'setPublished')) {
        $media->setPublished(FALSE);
        $changed = TRUE;
      }

      if ($media->hasField('moderation_state') && (string) $media->get('moderation_state')->value !== 'draft') {
        $media->set('moderation_state', 'draft');
        $changed = TRUE;
      }

      if ($changed) {
        $media->save();

        \Drupal::logger('jcc_pdf_upload_validation_checker')->notice(
          'Media @mid unpublished because file @fid was manually marked pending.',
          [
            '@mid' => $media->id(),
            '@fid' => $fid,
          ]
        );
      }
    }
  }

}
