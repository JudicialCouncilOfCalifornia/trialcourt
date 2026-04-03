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

  $config = $this->configFactory->get('jcc_pdf_upload_validation_checker.settings');
  $api = (string) ($config->get('pdf_validation_api') ?? 'pdf_audit');

  // Default to the current implementation unless explicitly set to equal_web.
  if ($api === 'equal_web') {
    return $this->auditWithEqualWeb($file, $config->get('equal_web_api_key'));
  }

  return $this->auditWithPdfAudit($file);
}

/**
 * Current/default API implementation.
 */
private function auditWithPdfAudit(FileInterface $file): array {
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

/**
 * EqualWeb implementation.
 */
private function auditWithEqualWeb(FileInterface $file, ?string $apiKey): array {
  if (empty($apiKey)) {
    return [
      'ok' => FALSE,
      'passed' => FALSE,
      'summary' => 'EqualWeb API key is missing.',
      'errors' => ['Missing equal_web_api_key configuration.'],
      'raw' => [],
    ];
  }

  $realPath = \Drupal::service('file_system')->realpath($file->getFileUri());
  if (!$realPath || !is_readable($realPath)) {
    return [
      'ok' => FALSE,
      'passed' => FALSE,
      'summary' => 'Unable to read PDF from disk.',
      'errors' => ['The PDF file could not be resolved to a readable local path.'],
      'raw' => [],
    ];
  }

  try {
    // Step 1: upload document.
    $uploadResponse = $this->httpClient->request(
      'PUT',
      'https://login.equalweb.com/api/v2/docs/upload',
      [
        'timeout' => 60,
        'connect_timeout' => 10,
        'http_errors' => FALSE,
        'headers' => [
          'Accept' => 'application/json',
          'x-a11y-api-key' => $apiKey,
        ],
        'multipart' => [
          [
            'name' => 'file',
            'contents' => fopen($realPath, 'rb'),
            'filename' => $file->getFilename(),
            'headers' => [
              'Content-Type' => 'application/pdf',
            ],
          ],
        ],
      ]
    );

    $uploadCode = $uploadResponse->getStatusCode();
    $uploadJson = json_decode((string) $uploadResponse->getBody(), TRUE) ?: [];

    if (!in_array($uploadCode, [200, 201], TRUE) || empty($uploadJson['id'])) {
      return [
        'ok' => FALSE,
        'passed' => FALSE,
        'summary' => 'EqualWeb upload failed.',
        'errors' => ['EqualWeb did not return a document ID.'],
        'raw' => [
          'upload' => $uploadJson,
        ],
        'status_code' => $uploadCode,
      ];
    }

    $reportId = $uploadJson['id'];

    // Step 2: start audit.
    $auditResponse = $this->httpClient->post(
      'https://login.equalweb.com/api/v2/docs/audit',
      [
        'timeout' => 60,
        'connect_timeout' => 10,
        'http_errors' => FALSE,
        'headers' => [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
          'x-a11y-api-key' => $apiKey,
        ],
        'json' => [
          'files' => [$reportId],
          'type' => 'audit',
        ],
      ]
    );

    $auditCode = $auditResponse->getStatusCode();
    $auditJson = json_decode((string) $auditResponse->getBody(), TRUE) ?: [];

    if (!in_array($auditCode, [200, 202], TRUE)) {
      return [
        'ok' => FALSE,
        'passed' => FALSE,
        'summary' => 'EqualWeb audit request failed.',
        'errors' => ['EqualWeb did not accept the audit request.'],
        'raw' => [
          'upload' => $uploadJson,
          'audit' => $auditJson,
        ],
        'status_code' => $auditCode,
      ];
    }

    // Step 3: poll until the report is ready.
    $statusJson = [];
    $status = NULL;
    $maxAttempts = 12;

    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
      if ($attempt > 0) {
        usleep(1500000); // 1.5 seconds
      }

      $statusResponse = $this->httpClient->get(
        'https://login.equalweb.com/api/v2/docs/status/' . rawurlencode($reportId),
        [
          'timeout' => 30,
          'connect_timeout' => 10,
          'http_errors' => FALSE,
          'headers' => [
            'Accept' => 'application/json',
            'x-a11y-api-key' => $apiKey,
          ],
        ]
      );

      $statusJson = json_decode((string) $statusResponse->getBody(), TRUE) ?: [];
      $status = strtolower((string) ($statusJson['status'] ?? ''));

      if ($status === 'done' || $status === 'completed') {
        break;
      }

      if (str_contains($status, 'failed')) {
        return [
          'ok' => FALSE,
          'passed' => FALSE,
          'summary' => 'EqualWeb audit failed.',
          'errors' => [$statusJson['status'] ?? 'EqualWeb reported a failed audit status.'],
          'raw' => [
            'upload' => $uploadJson,
            'audit' => $auditJson,
            'status' => $statusJson,
          ],
        ];
      }
    }

    if ($status !== 'done' && $status !== 'completed') {
      return [
        'ok' => TRUE,
        'passed' => FALSE,
        'summary' => 'EqualWeb audit started but did not finish in time.',
        'errors' => ['The report is not ready yet.'],
        'raw' => [
          'upload' => $uploadJson,
          'audit' => $auditJson,
          'status' => $statusJson,
        ],
      ];
    }

    // Step 4: fetch JSON report.
    $reportResponse = $this->httpClient->get(
      'https://login.equalweb.com/api/v2/docs/report/' . rawurlencode($reportId),
      [
        'timeout' => 60,
        'connect_timeout' => 10,
        'http_errors' => FALSE,
        'headers' => [
          'Accept' => 'application/json',
          'x-a11y-api-key' => $apiKey,
        ],
        'query' => [
          'type' => 'audited',
        ],
      ]
    );

    $reportCode = $reportResponse->getStatusCode();
    $reportJson = json_decode((string) $reportResponse->getBody(), TRUE) ?: [];

    if ($reportCode !== 200) {
      return [
        'ok' => FALSE,
        'passed' => FALSE,
        'summary' => 'EqualWeb report retrieval failed.',
        'errors' => ['Could not fetch EqualWeb report JSON.'],
        'raw' => [
          'upload' => $uploadJson,
          'audit' => $auditJson,
          'status' => $statusJson,
          'report' => $reportJson,
        ],
        'status_code' => $reportCode,
      ];
    }

    return $this->normalizeEqualWebReport(
      $reportJson,
      [
        'upload' => $uploadJson,
        'audit' => $auditJson,
        'status' => $statusJson,
      ],
      $reportCode
    );
  }
  catch (\Throwable $e) {
    return [
      'ok' => FALSE,
      'passed' => FALSE,
      'summary' => 'EqualWeb request failed.',
      'errors' => [$e->getMessage()],
      'raw' => [],
    ];
  }
}

/**
 * Normalize EqualWeb report into the same structure as the existing API.
 */
private function normalizeEqualWebReport(array $reportJson, array $context = [], int $statusCode = 200): array {
  $audited = $reportJson['audited'] ?? $reportJson;
  $summary = $audited['Summary'] ?? [];

  $failed = (int) ($summary['Failed'] ?? 0);
  $manualFailed = (int) ($summary['Failed manually'] ?? 0);
  $needsManualCheck = (int) ($summary['Needs manual check'] ?? 0);

  $passed = $failed === 0 && $manualFailed === 0 && $needsManualCheck === 0;

  $errors = [];

  if (!$passed) {
    if (!empty($audited['Detailed Report']) && is_array($audited['Detailed Report'])) {
      foreach ($audited['Detailed Report'] as $section => $items) {
        if (!is_array($items)) {
          continue;
        }

        foreach ($items as $item) {
          $itemStatus = strtolower((string) ($item['Status'] ?? ''));
          if (in_array($itemStatus, ['failed', 'failed manually', 'needs manual check'], TRUE)) {
            $rule = (string) ($item['Rule'] ?? 'Unknown rule');
            $description = (string) ($item['Description'] ?? '');
            $errors[] = trim($section . ': ' . $rule . ($description ? ' — ' . $description : ''));
          }
        }
      }
    }

    if (!$errors) {
      $errors[] = 'EqualWeb reported accessibility issues.';
    }
  }

  $summaryText = $passed
    ? 'PDF passed validation.'
    : sprintf(
      'PDF failed validation. Failed: %d, Failed manually: %d, Needs manual check: %d.',
      $failed,
      $manualFailed,
      $needsManualCheck
    );

  return [
    'ok' => TRUE,
    'passed' => $passed,
    'summary' => $summaryText,
    'errors' => $errors,
    'raw' => $reportJson + $context,
    'status_code' => $statusCode,
  ];
}

}
