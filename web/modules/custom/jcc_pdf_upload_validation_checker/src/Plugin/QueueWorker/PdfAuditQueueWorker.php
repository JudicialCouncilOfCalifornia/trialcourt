<?php

namespace Drupal\jcc_pdf_upload_validation_checker\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
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
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private EntityTypeManagerInterface $entityTypeManager,
    private ClientInterface $httpClient,
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
    $fid = (int) ($data['fid'] ?? 0);
    if (!$fid) {
      return;
    }
    \Drupal::logger('jcc_pdf_upload_validation_checker')->notice('Cron processing PDF validation on fid=@fid', ['@fid' => $fid]);

    /** @var \Drupal\file\FileInterface|null $file */
    $file = $this->entityTypeManager->getStorage('file')->load($fid);
    if (!$file) {
      return;
    }

    // Only PDFs.
    if ($file->getMimeType() !== 'application/pdf') {
      return;
    }

    // Only if field exists.
    if (!$file->hasField('field_pdf_audit_status')) {
      return;
    }

    // Read file bytes from URI.
    $uri = $file->getFileUri();
    $bytes = @file_get_contents($uri);
    if ($bytes === FALSE) {
      $file->set('field_pdf_audit_status', 'fail');
      $file->save();
      \Drupal::logger('jcc_pdf_upload_validation_checker')->notice('PDF fid=@fid failed validation', ['@fid' => $fid]);
      return;
    }

    $base64 = base64_encode($bytes);

    // Hardcoded call to PDFAudit.
    $response = $this->httpClient->post(
      'https://e3pyeerkgstf2covgz2yjkvj2m0bnqiq.lambda-url.us-east-1.on.aws/validate',
      [
        'timeout' => 60,
        'connect_timeout' => 10,
        'http_errors' => FALSE,
        'headers' => [
          'Accept' => 'application/json',
        ],
        'json' => [
          'include_raw' => TRUE,
          'pdf_base64' => $base64,
        ],
      ]
    );

    $code = $response->getStatusCode();
    $body = (string) $response->getBody();
    $json = json_decode($body, TRUE) ?: [];

    if ($code !== 200 || (empty($json['ok']) && empty($json['passed']))) {
      $file->set('field_pdf_audit_status', 'fail');
      $file->save();
      \Drupal::logger('jcc_pdf_upload_validation_checker')->notice('PDF fid=@fid failed validation', ['@fid' => $fid]);
      return;
    }

    $file->set('field_pdf_audit_status', 'pass');
    $file->save();
    \Drupal::logger('jcc_pdf_upload_validation_checker')->notice('PDF fid=@fid passed validation', ['@fid' => $fid]);
  }

}
