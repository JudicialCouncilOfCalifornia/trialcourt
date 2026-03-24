<?php

namespace Drupal\jcc_pdf_upload_validation_checker\PdfAudit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\file\FileInterface;
use GuzzleHttp\ClientInterface;

final class PdfAuditRunner {

  public function __construct(
    private ClientInterface $httpClient,
    private ConfigFactoryInterface $configFactory,
  ) {}

/**
 * Run the remote PDF audit and return a normalized result.
 */
public function auditFile(FileInterface $file): array {
  if ($file->getMimeType() !== 'application/pdf') {
    return [
      'ok' => FALSE,
      'passed' => FALSE,
      'summary' => 'File is not a PDF.',
      'errors' => ['File is not a PDF.'],
      'raw' => [],
    ];
  }

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
