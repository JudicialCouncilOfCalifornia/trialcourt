<?php

namespace Drupal\jcc_pdf_upload_validation_checker\PdfAudit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\file\FileInterface;
use GuzzleHttp\ClientInterface;

/**
 * Service responsible for running PDF accessibility audits.
 *
 * Acts as a wrapper for EqualWeb and PDFaudit APIs
 * and normalizes their responses into a consistent
 * structure used by the module.
 */
final class PdfAuditRunner {

  /**
   * Constructs the PdfAuditRunner service.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client used to communicate with external validation APIs.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory used to retrieve module settings.
   */
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

    if ($api === 'EqualWeb' || $api === 'equal_web' || $api === 'equalweb') {
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

  /**
   * EqualWeb implementation.
   */
  private function auditWithEqualWeb(FileInterface $file, ?string $apiKey): array {
    $apiKey = trim((string) $apiKey);

    if ($apiKey === '') {
      $this->markEqualWebSummary(
        $file,
        ['Missing equal_web_api_key configuration.'],
        'EqualWeb API key is missing.'
      );

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
      $this->markEqualWebSummary(
        $file,
        ['The PDF file could not be resolved to a readable local path.'],
        'Unable to read PDF from disk.'
      );

      return [
        'ok' => FALSE,
        'passed' => FALSE,
        'summary' => 'Unable to read PDF from disk.',
        'errors' => ['The PDF file could not be resolved to a readable local path.'],
        'raw' => [],
      ];
    }

    try {
      \Drupal::logger('jcc_pdf_upload_validation_checker')->notice(
        'EqualWeb upload request: method=PUT path=@path filename=@filename mime=@mime size=@size',
        [
          '@path' => $realPath,
          '@filename' => basename($realPath),
          '@mime' => mime_content_type($realPath) ?: 'unknown',
          '@size' => file_exists($realPath) ? filesize($realPath) : 0,
        ]
      );

      // Step 1: upload document.
      $handle = fopen($realPath, 'rb');

      $uploadResponse = $this->httpClient->request(
        'PUT',
        'https://login.equalweb.com/api/v2/docs/upload',
        [
          'timeout' => 240,
          'connect_timeout' => 10,
          'http_errors' => FALSE,
          'headers' => [
            'Accept' => 'application/json',
            'x-a11y-api-key' => $apiKey,
          ],
          'multipart' => [
            [
              'name' => 'file',
              'contents' => $handle,
              'filename' => basename($realPath),
            ],
          ],
        ]
      );

      if (is_resource($handle)) {
        fclose($handle);
      }

      $uploadCode = $uploadResponse->getStatusCode();
      $uploadBody = (string) $uploadResponse->getBody();
      $uploadJson = json_decode($uploadBody, TRUE) ?: [];

      \Drupal::logger('jcc_pdf_upload_validation_checker')->notice(
        'EqualWeb upload response: status=@status body=@body',
        [
          '@status' => $uploadCode,
          '@body' => $uploadBody,
        ]
      );

      $reportId =
        $uploadJson['id']
        ?? $uploadJson['data']['id']
        ?? $uploadJson['fileId']
        ?? $uploadJson['document_id']
        ?? NULL;

      if (!in_array($uploadCode, [200, 201], TRUE) || empty($reportId)) {
        $readableErrors = [
          'Upload HTTP status: ' . $uploadCode,
          'Upload response: ' . ($uploadBody !== '' ? $uploadBody : '[empty response]'),
        ];

        $this->markEqualWebSummary(
          $file,
          $readableErrors,
          'EqualWeb upload failed.'
        );

        return [
          'ok' => FALSE,
          'passed' => FALSE,
          'summary' => 'EqualWeb upload failed.',
          'errors' => $readableErrors,
          'raw' => [
            'upload' => $uploadJson,
            'upload_body' => $uploadBody,
          ],
          'status_code' => $uploadCode,
        ];
      }

      // Step 2: start audit.
      $auditResponse = $this->httpClient->post(
        'https://login.equalweb.com/api/v2/docs/audit',
        [
          'timeout' => 240,
          'connect_timeout' => 10,
          'http_errors' => FALSE,
          'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'x-a11y-api-key' => $apiKey,
          ],
          'json' => [
            'files' => [$reportId],
            'type' => 'check',
          ],
        ]
      );

      $auditCode = $auditResponse->getStatusCode();
      $auditBody = (string) $auditResponse->getBody();
      $auditJson = json_decode($auditBody, TRUE) ?: [];

      \Drupal::logger('jcc_pdf_upload_validation_checker')->notice(
        'EqualWeb audit response: status=@status body=@body',
        [
          '@status' => $auditCode,
          '@body' => $auditBody,
        ]
      );

      if (!in_array($auditCode, [200, 202], TRUE)) {
        $errors = [
          'Audit HTTP status: ' . $auditCode,
          'Audit response: ' . ($auditBody !== '' ? $auditBody : '[empty response]'),
        ];

        $this->markEqualWebSummary(
          $file,
          $errors,
          'EqualWeb audit request failed.'
        );

        return [
          'ok' => FALSE,
          'passed' => FALSE,
          'summary' => 'EqualWeb audit request failed.',
          'errors' => $errors,
          'raw' => [
            'upload' => $uploadJson,
            'audit' => $auditJson,
            'audit_body' => $auditBody,
          ],
          'status_code' => $auditCode,
        ];
      }

      // Step 3: poll until the report is ready.
      $statusJson = [];
      $status = NULL;
      $maxAttempts = 180;

      for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
        if ($attempt > 0) {
          sleep(5);
        }

        $statusResponse = $this->httpClient->get(
          'https://login.equalweb.com/api/v2/docs/status/' . rawurlencode($reportId),
          [
            'timeout' => 240,
            'connect_timeout' => 10,
            'http_errors' => FALSE,
            'headers' => [
              'Accept' => 'application/json',
              'x-a11y-api-key' => $apiKey,
            ],
          ]
        );

        $statusBody = (string) $statusResponse->getBody();
        $statusJson = json_decode($statusBody, TRUE) ?: [];
        $status = strtolower((string) ($statusJson['status'] ?? ''));

        \Drupal::logger('jcc_pdf_upload_validation_checker')->notice(
          'EqualWeb status response: attempt=@attempt status=@status body=@body',
          [
            '@attempt' => $attempt + 1,
            '@status' => $status,
            '@body' => $statusBody,
          ]
        );

        if (in_array($status, ['done', 'completed'], TRUE)) {
          break;
        }

        if (str_contains($status, 'failed')) {
          $errors = [
            'Status response: ' . ($statusBody !== '' ? $statusBody : '[empty response]'),
            'Status value: ' . ($statusJson['status'] ?? '[missing]'),
          ];

          $this->markEqualWebSummary(
            $file,
            $errors,
            'EqualWeb audit failed.'
          );

          return [
            'ok' => FALSE,
            'passed' => FALSE,
            'summary' => 'EqualWeb audit failed.',
            'errors' => $errors,
            'raw' => [
              'upload' => $uploadJson,
              'audit' => $auditJson,
              'status' => $statusJson,
            ],
          ];
        }

        // Keep waiting for intermediate states like:
        // pending, ocr, queued, processing, etc.
      }

      if ($status !== 'done' && $status !== 'completed') {
        \Drupal::logger('jcc_pdf_upload_validation_checker')->notice(
          'EqualWeb polling ended after @attempts attempts. Last status: @status',
          [
            '@attempts' => $maxAttempts,
            '@status' => json_encode($statusJson),
          ]
        );

        $errors = [
          'Last status response: ' . json_encode($statusJson),
        ];

        $this->markEqualWebSummary(
          $file,
          $errors,
          'EqualWeb audit is still processing.'
        );

        return [
          'ok' => TRUE,
          'passed' => FALSE,
          'summary' => 'EqualWeb audit is still processing.',
          'errors' => $errors,
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
          'timeout' => 240,
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
      $reportBody = (string) $reportResponse->getBody();
      $reportJson = json_decode($reportBody, TRUE) ?: [];

      \Drupal::logger('jcc_pdf_upload_validation_checker')->notice(
        'EqualWeb report response: status=@status body=@body',
        [
          '@status' => $reportCode,
          '@body' => $reportBody,
        ]
      );

      if ($reportCode !== 200) {
        $errors = [
          'Report HTTP status: ' . $reportCode,
          'Report response: ' . ($reportBody !== '' ? $reportBody : '[empty response]'),
        ];

        $this->markEqualWebSummary(
          $file,
          $errors,
          'EqualWeb report retrieval failed.'
        );

        return [
          'ok' => FALSE,
          'passed' => FALSE,
          'summary' => 'EqualWeb report retrieval failed.',
          'errors' => $errors,
          'raw' => [
            'upload' => $uploadJson,
            'audit' => $auditJson,
            'status' => $statusJson,
            'report' => $reportJson,
            'report_body' => $reportBody,
          ],
          'status_code' => $reportCode,
        ];
      }

      return $this->normalizeEqualWebReport(
        $file,
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
      $this->markEqualWebSummary(
        $file,
        [$e->getMessage()],
        'EqualWeb request failed.'
      );

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
  private function normalizeEqualWebReport(FileInterface $file, array $reportJson, array $context = [], int $statusCode = 200): array {
    $errors = [];
    $failed = 0;
    $manualFailed = 0;
    $needsManualCheck = 0;
    $recognizedSignal = FALSE;

    if (!empty($reportJson['totalIssues']) && is_array($reportJson['totalIssues'])) {
      $totals = $reportJson['totalIssues'];

      // Only actual EqualWeb failures should block pass.
      $failed = (int) ($totals['failed'] ?? 0);

      // Keep warnings for reporting only.
      $needsManualCheck = (int) ($totals['warning'] ?? 0);

      $recognizedSignal = TRUE;
    }

    if (!empty($reportJson['checkerReport']) && is_array($reportJson['checkerReport'])) {
      $recognizedSignal = TRUE;

      $failedRuleCount = 0;
      $warningRuleCount = 0;

      foreach ($reportJson['checkerReport'] as $item) {
        if (!is_array($item)) {
          continue;
        }

        $name = (string) ($item['name'] ?? 'Unknown check');
        $description = (string) ($item['description'] ?? '');
        $ruleId = (string) ($item['rule_id'] ?? '');
        $errorCount = (int) ($item['error'] ?? 0);
        $warningCount = (int) ($item['warning'] ?? 0);

        if ($errorCount > 0) {
          $failedRuleCount++;

          $label = $name;
          if ($ruleId !== '') {
            $label .= ' (' . $ruleId . ')';
          }
          if ($description !== '') {
            $label .= ' — ' . $description;
          }
          $label .= ' [errors: ' . $errorCount . ']';

          $errors[] = $label;
        }
        elseif ($warningCount > 0) {
          $warningRuleCount++;
        }
      }

      // Fallback if totalIssues is absent.
      if (empty($reportJson['totalIssues']) || !is_array($reportJson['totalIssues'])) {
        $failed = $failedRuleCount;
        $needsManualCheck = $warningRuleCount;
      }
    }

    // Warnings/manual checks do not block pass.
    $passed = $recognizedSignal && $failed === 0 && $manualFailed === 0;

    if (!$recognizedSignal) {
      $errors[] = 'EqualWeb report format was not recognized.';
    }
    elseif (!$passed && !$errors) {
      $errors[] = 'EqualWeb reported accessibility issues.';
    }

    $summaryText = $passed
      ? ($needsManualCheck > 0
        ? sprintf('PDF passed validation with %d warning(s) / manual review item(s).', $needsManualCheck)
        : 'PDF passed validation.')
      : sprintf(
        'PDF failed validation. Failed: %d, Failed manually: %d, Needs manual check: %d.',
        $failed,
        $manualFailed,
        $needsManualCheck
      );

    \Drupal::logger('jcc_pdf_upload_validation_checker')->notice(
      'EqualWeb normalized report: passed=@passed failed=@failed manual_failed=@manual_failed needs_manual=@needs_manual',
      [
        '@passed' => $passed ? 'true' : 'false',
        '@failed' => $failed,
        '@manual_failed' => $manualFailed,
        '@needs_manual' => $needsManualCheck,
      ]
    );

    if ($passed) {
      $this->clearEqualWebSummary($file);
    }
    else {
      $this->markEqualWebSummary($file, $errors, $summaryText);
    }

    return [
      'ok' => TRUE,
      'passed' => $passed,
      'summary' => $summaryText,
      'errors' => $errors,
      'raw' => $reportJson + $context,
      'status_code' => $statusCode,
    ];
  }

  /**
   * Write a readable summary to the file entity.
   */
  private function markEqualWebSummary(FileInterface $file, array $errors = [], string $summary = ''): void {
    if (!$file->hasField('field_pdf_audit_summary')) {
      return;
    }

    try {
      $text = $summary ?: 'EqualWeb validation failed.';

      if (!empty($errors)) {
        $text .= "\n\nIssues:\n- " . implode("\n- ", $errors);
      }

      $maxLength = 20000;
      if (strlen($text) > $maxLength) {
        $text = substr($text, 0, $maxLength) . "\n\n[truncated]";
      }

      $file->set('field_pdf_audit_summary', $text);
      $file->save();
    }
    catch (\Throwable $e) {
      \Drupal::logger('jcc_pdf_upload_validation_checker')->error(
        'Unable to write audit summary for file @fid: @message',
        [
          '@fid' => $file->id(),
          '@message' => $e->getMessage(),
        ]
      );
    }
  }

  /**
   * Clear the summary field when validation passes.
   */
  private function clearEqualWebSummary(FileInterface $file): void {
    if (!$file->hasField('field_pdf_audit_summary')) {
      return;
    }

    try {
      $file->set('field_pdf_audit_summary', '');
      $file->save();
    }
    catch (\Throwable $e) {
      \Drupal::logger('jcc_pdf_upload_validation_checker')->error(
        'Unable to clear field_pdf_audit_summary for file @fid: @message',
        [
          '@fid' => $file->id(),
          '@message' => $e->getMessage(),
        ]
      );
    }
  }

}
