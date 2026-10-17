<?php

namespace Drupal\jcc_pdf_upload_validation_checker\PdfAudit\Api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drupal\jcc_pdf_upload_validation_checker\PdfAudit\PdfAuditApiInterface;
use GuzzleHttp\ClientInterface;

/**
 * EqualWeb PDF accessibility audit API implementation.
 */
final class EqualWebPdfAuditApi implements PdfAuditApiInterface {

  public const ID = 'equal_web';

  private const REPORT_BASE_URL = 'https://login.equalweb.com/reports/pdf/';

  /**
   * Constructs the EqualWebPdfAuditApi service.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client used to communicate with the EqualWeb API.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service, used to resolve a local path to upload.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory, used to read the EqualWeb API key at
   *   request time so config changes take effect without a container rebuild.
   */
  public function __construct(
    private ClientInterface $httpClient,
    private FileSystemInterface $fileSystem,
    private ConfigFactoryInterface $configFactory,
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
    $apiKey = trim((string) $this->configFactory
      ->get('jcc_pdf_upload_validation_checker.settings')
      ->get('equal_web_api_key'));

    if ($apiKey === '') {
      $this->markSummary(
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

    $realPath = $this->fileSystem->realpath($file->getFileUri());
    if (!$realPath || !is_readable($realPath)) {
      $this->markSummary(
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

        $this->markSummary(
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
      // Brief pause to allow EqualWeb to finish processing the uploaded file
      // before triggering the audit; firing immediately returns "No pages found".
      sleep(3);

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

      if (!in_array($auditCode, [200, 202], TRUE)) {
        $errors = [
          'Audit HTTP status: ' . $auditCode,
          'Audit response: ' . ($auditBody !== '' ? $auditBody : '[empty response]'),
        ];

        $this->markSummary(
          $file,
          $errors,
          'EqualWeb audit request failed.',
          $reportId
        );

        return [
          'ok' => FALSE,
          'passed' => FALSE,
          'summary' => 'EqualWeb audit request failed.',
          'report_id' => $reportId,
          'report_url' => $this->buildReportUrl($reportId),
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
      $reportType = 'audited';
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
        $auditStatus = strtolower((string) ($statusJson['auditStatus'] ?? ''));
        $type = strtolower((string) ($statusJson['type'] ?? ''));
        if (in_array($type, ['check', 'audit', 'manual', 'convert'], TRUE)) {
          $reportType = 'audited';
        }
        elseif ($type === 'uploading') {
          $reportType = 'original';
        }

        if (in_array($status, ['ready', 'done', 'completed'], TRUE) || $auditStatus === 'done') {
          break;
        }

        if (in_array($status, ['failed', 'deleted'], TRUE) || str_contains($status, 'failed')) {
          $errors = [
            'Status response: ' . ($statusBody !== '' ? $statusBody : '[empty response]'),
            'Status value: ' . ($statusJson['status'] ?? '[missing]'),
          ];

          $this->markSummary(
            $file,
            $errors,
            'EqualWeb audit failed.',
            $reportId
          );

          return [
            'ok' => FALSE,
            'passed' => FALSE,
            'summary' => 'EqualWeb audit failed.',
            'report_id' => $reportId,
            'report_url' => $this->buildReportUrl($reportId),
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

      if (!in_array($status, ['ready', 'done', 'completed'], TRUE)) {
        $errors = [
          'Last status response: ' . json_encode($statusJson),
        ];

        $this->markSummary(
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
      $reportTypes = array_values(array_unique(array_filter([
        $reportType,
        'both',
        'original',
        'audited',
      ])));
      $reportCode = 0;
      $reportBody = '';
      $reportJson = [];

      foreach ($reportTypes as $candidateReportType) {
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
              'type' => $candidateReportType,
            ],
          ]
        );

        $reportCode = $reportResponse->getStatusCode();
        $reportBody = (string) $reportResponse->getBody();
        $reportJson = json_decode($reportBody, TRUE) ?: [];

        if ($reportCode === 200) {
          break;
        }

        $reportError = strtolower((string) ($reportJson['error'] ?? ''));
        $canRetry = $reportCode === 400
          || $reportCode === 404
          || str_contains($reportError, 'not found')
          || str_contains($reportError, 'allowed values');

        if (!$canRetry) {
          break;
        }
      }

      if ($reportCode !== 200) {
        $errors = [
          'Report HTTP status: ' . $reportCode,
          'Report response: ' . ($reportBody !== '' ? $reportBody : '[empty response]'),
        ];

        $this->markSummary(
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

      return $this->normalizeReport(
        $file,
        $reportJson,
        [
          'upload' => $uploadJson,
          'audit' => $auditJson,
          'status' => $statusJson,
        ],
        $reportCode,
        $reportId
      );
    }
    catch (\Throwable $e) {
      $this->markSummary(
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
   * Builds the hosted report URL for a given EqualWeb report id.
   */
  private function buildReportUrl(string $reportId): string {
    return self::REPORT_BASE_URL . rawurlencode($reportId);
  }

  /**
   * Normalize EqualWeb report into the module's common result structure.
   */
  private function normalizeReport(FileInterface $file, array $reportJson, array $context = [], int $statusCode = 200, string $reportId = ''): array {
    if (!empty($reportJson['audited']) && is_array($reportJson['audited'])) {
      $reportJson = $reportJson['audited'] + ['original' => $reportJson['original'] ?? NULL];
    }
    elseif (!empty($reportJson['original']) && is_array($reportJson['original'])) {
      $reportJson = $reportJson['original'];
    }

    $errors = [];
    $failed = 0;
    $blockingRuleMatches = 0;
    $blockingErrorCount = 0;
    $nonBlockingRuleMatches = 0;
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
        $wcagLevel = trim((string) (
          $item['wcag_level']
          ?? $item['wcagLevel']
          ?? $item['wcag']
          ?? ''
        ));

        if ($errorCount > 0) {
          $failedRuleCount++;

          $label = $name;
          if ($ruleId !== '') {
            $label .= ' (' . $ruleId . ')';
          }
          if ($description !== '') {
            $label .= ' — ' . $description;
          }
          $label .= ' [errors: ' . $errorCount;
          if ($wcagLevel !== '') {
            $label .= ', wcag_level: ' . $wcagLevel;
          }
          $label .= ']';

          // Blocking rule: same check item must have errors and a WCAG level.
          if ($wcagLevel !== '') {
            $blockingRuleMatches++;
            $blockingErrorCount += $errorCount;
            $errors[] = $label;
          }
          else {
            $nonBlockingRuleMatches++;
          }
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

    if (!empty($reportJson['Summary']) && is_array($reportJson['Summary'])) {
      $recognizedSignal = TRUE;

      $summary = $reportJson['Summary'];
      $failed = (int) ($summary['Failed'] ?? $failed);
      $manualFailed = (int) ($summary['Failed manually'] ?? $manualFailed);
      $needsManualCheck = (int) ($summary['Needs manual check'] ?? $needsManualCheck);
    }

    if (!empty($reportJson['Detailed Report']) && is_array($reportJson['Detailed Report'])) {
      $recognizedSignal = TRUE;
    }

    // Only this rule blocks validation: same item has errors and WCAG level.
    $passed = $recognizedSignal && $blockingRuleMatches === 0;

    if (!$recognizedSignal) {
      $errors[] = 'EqualWeb report format was not recognized.';
    }
    elseif (!$passed && !$errors) {
      $errors[] = 'EqualWeb reported accessibility issues.';
    }

    if ($passed) {
      if ($nonBlockingRuleMatches > 0 || $needsManualCheck > 0) {
        $summaryText = sprintf(
          "PDF passed validation.\n\nSome accessibility issues are not blocking but should be corrected when possible.\n- Non-blocking issues: %d\n- Manual review items: %d\n\nRule: a check blocks validation only when it doesn't meet WCAG standards (A or AA) See the full report linked above for results of the validation check.",
          $nonBlockingRuleMatches,
          $needsManualCheck
        );
      }
      else {
        $summaryText = 'PDF passed validation.';
      }
    }
    else {
      $summaryText = sprintf(
        "PDF did not pass validation.\n\nPlease fix the blocking accessibility issues and re-upload.\n- Blocking issues: %d\n- Error instances: %d\n\nRule: a check blocks validation only when it doesn't meet WCAG standards (A or AA) See the full report linked above for results of the validation check.",
        $blockingRuleMatches,
        $blockingErrorCount
      );
    }
    $this->markSummary($file, $passed ? [] : $errors, $summaryText, $reportId);

    return [
      'ok' => TRUE,
      'passed' => $passed,
      'summary' => $summaryText,
      'errors' => $errors,
      'raw' => $reportJson + $context,
      'status_code' => $statusCode,
      'report_id' => $reportId,
      'report_url' => $reportId !== '' ? $this->buildReportUrl($reportId) : NULL,
    ];
  }

  /**
   * Write a readable summary to the file entity.
   */
  private function markSummary(FileInterface $file, array $errors = [], string $summary = '', string $reportId = ''): void {
    if (!$file->hasField('field_pdf_audit_summary')) {
      return;
    }

    try {
      $text = $summary ?: 'EqualWeb validation failed.';

      if ($reportId !== '') {
        $text .= "\n\nView full report: " . $this->buildReportUrl($reportId);
      }

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

}
