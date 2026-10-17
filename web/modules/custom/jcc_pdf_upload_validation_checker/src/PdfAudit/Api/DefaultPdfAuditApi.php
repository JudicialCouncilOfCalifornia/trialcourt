<?php

namespace Drupal\jcc_pdf_upload_validation_checker\PdfAudit\Api;

use Drupal\file\FileInterface;
use Drupal\jcc_pdf_upload_validation_checker\PdfAudit\PdfAuditApiInterface;
use GuzzleHttp\ClientInterface;

/**
 * Default PDF audit API (in-house Lambda-backed validation service).
 */
final class DefaultPdfAuditApi implements PdfAuditApiInterface {

  public const ID = 'pdf_audit';

  /**
   * Constructs the DefaultPdfAuditApi service.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client used to communicate with the validation service.
   */
  public function __construct(
    private ClientInterface $httpClient,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return self::ID;
  }

  /**
   * {@inheritdoc}
   */
  public function audit(FileInterface $file): array {
    $uri = $file->getFileUri();
    $bytes = @file_get_contents($uri);

    if ($bytes === FALSE) {
      return [
        'ok' => FALSE,
        'passed' => FALSE,
        'summary' => 'Unable to read file bytes.',
        'errors' => ['Unable to read file bytes.'],
        'raw' => [],
      ];
    }

    $base64 = base64_encode($bytes);

    try {
      $response = $this->httpClient->post(
        'https://e3pyeerkgstf2covgz2yjkvj2m0bnqiq.lambda-url.us-east-1.on.aws/validate',
        [
          'timeout' => 240,
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
    }
    catch (\Throwable $e) {
      return [
        'ok' => FALSE,
        'passed' => FALSE,
        'summary' => 'Audit request failed.',
        'errors' => [$e->getMessage()],
        'raw' => [],
      ];
    }

    $code = $response->getStatusCode();
    $body = (string) $response->getBody();
    $json = json_decode($body, TRUE) ?: [];

    $passed = $code === 200 && (!empty($json['ok']) || !empty($json['passed']));

    return [
      'ok' => $code === 200,
      'passed' => $passed,
      'summary' => $passed ? 'PDF passed validation.' : 'PDF failed validation.',
      'errors' => $passed ? [] : ['Validation service returned a failure response.'],
      'raw' => $json,
      'status_code' => $code,
    ];
  }

}
