<?php

namespace Drupal\jcc_pdf_upload_validation_checker\Apis\Api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drupal\jcc_pdf_upload_validation_checker\Apis\PdfAuditApiInterface;
use GuzzleHttp\ClientInterface;

/**
 * Planetaria PDF accessibility audit API implementation.
 *
 * Backed by the Inquiros PDF Remediation integration API (/api/v1), per the
 * vendor's "Inquiros PDF Remediation: API and Integration Guide" (Oct 2026).
 * Only the validation (audit) half of that API is used here — documents are
 * submitted and polled until a verdict is reached; remediation/fix,
 * translation, webhooks and bulk endpoints are out of scope for this
 * module, which only needs a pass/fail verdict at upload time.
 *
 * The vendor's `compliant` field is the account's own verdict, evaluated
 * against whichever WCAG conformance level is configured for the account in
 * the Inquiros dashboard. This site must have that account validation
 * setting configured to WCAG 2.2 for this backend's pass/fail result to
 * match the module's WCAG 2.2 requirement; this class does not re-derive a
 * WCAG level from individual issues, since the API does not document a
 * stable per-issue schema for that.
 */
final class PlanetariaPdfAuditApi implements PdfAuditApiInterface {

  public const ID = 'planetaria';

  private const PRODUCTION_BASE_URL = 'https://pdfchecker.inquiros.io/api/v1';

  private const STAGING_BASE_URL = 'https://staging.pdfchecker.inquiros.io/api/v1';

  private const PRODUCTION_WEB_URL = 'https://pdfchecker.inquiros.io';

  private const STAGING_WEB_URL = 'https://staging.pdfchecker.inquiros.io';

  /**
   * Constructs the PlanetariaPdfAuditApi service.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client used to communicate with the Planetaria/Inquiros API.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service, used to resolve a local path to upload.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory, used to read the Planetaria API key at
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
   * Returns the API base URL, honoring the staging environment toggle.
   *
   * Staging keys (e.g. from https://staging.pdfchecker.inquiros.io) are
   * rejected by the production API with 401 API_KEY_ENVIRONMENT, so the
   * configured environment must match the key's own environment.
   */
  private function getBaseUrl(): string {
    $useStaging = (bool) $this->configFactory
      ->get('jcc_pdf_upload_validation_checker.settings')
      ->get('planetaria_use_staging');

    return $useStaging ? self::STAGING_BASE_URL : self::PRODUCTION_BASE_URL;
  }

  /**
   * Builds the hosted dashboard report URL for a given Planetaria scan id.
   *
   * The document id returned by POST /documents doubles as the dashboard's
   * scanId query param; the dashboard lives at the web root (not /api/v1)
   * and also switches between staging/production with the same toggle.
   */
  private function buildReportUrl(string $documentId): string {
    $useStaging = (bool) $this->configFactory
      ->get('jcc_pdf_upload_validation_checker.settings')
      ->get('planetaria_use_staging');
    $webUrl = $useStaging ? self::STAGING_WEB_URL : self::PRODUCTION_WEB_URL;

    return $webUrl . '/?scanId=' . rawurlencode($documentId) . '&page=results';
  }

  /**
   * {@inheritdoc}
   */
  public function audit(FileInterface $file): array {
    $apiKey = trim((string) $this->configFactory
      ->get('jcc_pdf_upload_validation_checker.settings')
      ->get('planetaria_api_key'));

    if ($apiKey === '') {
      $this->markSummary($file, ['Missing planetaria_api_key configuration.'], 'Planetaria API key is missing.');

      return [
        'ok' => FALSE,
        'passed' => FALSE,
        'summary' => 'Planetaria API key is missing.',
        'errors' => ['Missing planetaria_api_key configuration.'],
        'raw' => [],
      ];
    }

    $realPath = $this->fileSystem->realpath($file->getFileUri());
    if (!$realPath || !is_readable($realPath)) {
      $this->markSummary($file, ['The PDF file could not be resolved to a readable local path.'], 'Unable to read PDF from disk.');

      return [
        'ok' => FALSE,
        'passed' => FALSE,
        'summary' => 'Unable to read PDF from disk.',
        'errors' => ['The PDF file could not be resolved to a readable local path.'],
        'raw' => [],
      ];
    }

    try {
      // Step 1: submit the PDF for validation. The API audits automatically
      // on submission; there is no separate "start audit" call like EqualWeb.
      $handle = fopen($realPath, 'rb');

      $submitResponse = $this->httpClient->request(
        'POST',
        $this->getBaseUrl() . '/documents',
        [
          'timeout' => 240,
          'connect_timeout' => 10,
          'http_errors' => FALSE,
          'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/pdf',
            'Authorization' => 'Bearer ' . $apiKey,
          ],
          'body' => $handle,
        ]
      );

      if (is_resource($handle)) {
        fclose($handle);
      }

      $submitCode = $submitResponse->getStatusCode();
      $submitBody = (string) $submitResponse->getBody();
      $submitJson = json_decode($submitBody, TRUE) ?: [];
      $documentId = (string) ($submitJson['id'] ?? '');

      if ($submitCode !== 202 || $documentId === '') {
        $errors = $this->describeErrorResponse($submitCode, $submitJson, $submitBody);

        $this->markSummary($file, $errors, 'Planetaria submission failed.');

        return [
          'ok' => FALSE,
          'passed' => FALSE,
          'summary' => 'Planetaria submission failed.',
          'errors' => $errors,
          'raw' => ['submit' => $submitJson, 'submit_body' => $submitBody],
          'status_code' => $submitCode,
        ];
      }

      // Step 2: poll until validation finishes.
      $document = [];
      $status = '';
      $maxAttempts = 180;

      for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
        if ($attempt > 0) {
          sleep(5);
        }

        $pollResponse = $this->httpClient->get(
          $this->getBaseUrl() . '/documents/' . rawurlencode($documentId),
          [
            'timeout' => 240,
            'connect_timeout' => 10,
            'http_errors' => FALSE,
            'headers' => [
              'Accept' => 'application/json',
              'Authorization' => 'Bearer ' . $apiKey,
            ],
          ]
        );

        $pollBody = (string) $pollResponse->getBody();
        $polledJson = json_decode($pollBody, TRUE) ?: [];
        if ($polledJson !== []) {
          $document = $polledJson;
        }
        $status = strtolower((string) ($document['status'] ?? ''));

        if (in_array($status, ['completed', 'error'], TRUE)) {
          break;
        }

        // Keep waiting for intermediate states like: queued, processing.
      }

      if ($status === 'error') {
        $errors = [(string) ($document['message'] ?? 'Planetaria reported a validation error.')];

        $this->markSummary($file, $errors, 'Planetaria validation failed.', $documentId);

        return [
          'ok' => FALSE,
          'passed' => FALSE,
          'summary' => 'Planetaria validation failed.',
          'report_id' => $documentId,
          'report_url' => $this->buildReportUrl($documentId),
          'errors' => $errors,
          'raw' => ['document' => $document],
        ];
      }

      if ($status !== 'completed') {
        $errors = ['Last document status: ' . ($status !== '' ? $status : '[unknown]')];

        $this->markSummary($file, $errors, 'Planetaria validation is still processing.', $documentId);

        return [
          'ok' => TRUE,
          'passed' => FALSE,
          'summary' => 'Planetaria validation is still processing.',
          'report_id' => $documentId,
          'report_url' => $this->buildReportUrl($documentId),
          'errors' => $errors,
          'raw' => ['document' => $document],
        ];
      }

      // Step 3: fetch the issue list for a human-readable breakdown.
      $issuesResponse = $this->httpClient->get(
        $this->getBaseUrl() . '/documents/' . rawurlencode($documentId),
        [
          'timeout' => 240,
          'connect_timeout' => 10,
          'http_errors' => FALSE,
          'headers' => [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
          ],
          'query' => ['include' => 'issues'],
        ]
      );

      $issuesBody = (string) $issuesResponse->getBody();
      $issuesJson = json_decode($issuesBody, TRUE) ?: [];
      if ($issuesJson !== []) {
        $document = $issuesJson;
      }

      return $this->normalizeDocument($file, $document, $documentId);
    }
    catch (\Throwable $e) {
      $this->markSummary($file, [$e->getMessage()], 'Planetaria request failed.');

      return [
        'ok' => FALSE,
        'passed' => FALSE,
        'summary' => 'Planetaria request failed.',
        'errors' => [$e->getMessage()],
        'raw' => [],
      ];
    }
  }

  /**
   * Builds a readable error list from a non-2xx JSON response.
   */
  private function describeErrorResponse(int $statusCode, array $json, string $body): array {
    $errors = ['HTTP status: ' . $statusCode];

    if (!empty($json['message'])) {
      $errors[] = 'Message: ' . $json['message'];
    }
    if (!empty($json['code'])) {
      $errors[] = 'Code: ' . $json['code'];
    }
    if (empty($json['message']) && empty($json['code'])) {
      $errors[] = 'Response: ' . ($body !== '' ? $body : '[empty response]');
    }

    return $errors;
  }

  /**
   * Normalize a completed Planetaria Document into the module's result shape.
   *
   * `compliant` is the vendor's own verdict under the account's configured
   * validation settings (expected to be WCAG 2.2 on this account); this
   * method trusts that verdict rather than re-deriving WCAG levels from
   * individual issues, since the vendor does not document a stable
   * per-issue schema. `issueCount` vs `countedIssueCount` is used only to
   * split blocking vs non-blocking issues for the human-readable summary.
   */
  private function normalizeDocument(FileInterface $file, array $document, string $documentId): array {
    $compliant = $document['compliant'] ?? NULL;
    $recognizedSignal = is_bool($compliant);
    $passed = $recognizedSignal && $compliant === TRUE;

    $issueCount = (int) ($document['issueCount'] ?? 0);
    $countedIssueCount = (int) ($document['countedIssueCount'] ?? $issueCount);
    $nonBlockingCount = max(0, $issueCount - $countedIssueCount);

    $errors = [];
    foreach ((array) ($document['issues'] ?? []) as $issue) {
      if (!is_array($issue)) {
        continue;
      }

      $label = (string) ($issue['message'] ?? $issue['description'] ?? $issue['title'] ?? 'Issue');
      $criterion = (string) ($issue['wcagCriterion'] ?? $issue['successCriterion'] ?? $issue['criterion'] ?? $issue['wcag'] ?? '');
      $severity = (string) ($issue['severity'] ?? $issue['level'] ?? $issue['impact'] ?? '');
      $page = $issue['page'] ?? $issue['pageNumber'] ?? NULL;

      $details = [];
      if ($criterion !== '') {
        $details[] = 'WCAG ' . $criterion;
      }
      if ($severity !== '') {
        $details[] = $severity;
      }
      if ($page !== NULL) {
        $details[] = 'page ' . $page;
      }
      if ($details !== []) {
        $label .= ' [' . implode(', ', $details) . ']';
      }

      $errors[] = $label;
    }

    if (!$recognizedSignal) {
      $errors[] = 'Planetaria response format was not recognized.';
    }
    elseif (!$passed && $errors === []) {
      $errors[] = 'Planetaria reported accessibility issues.';
    }

    if ($passed) {
      $summaryText = $nonBlockingCount > 0
        ? sprintf(
          "PDF passed validation.\n\nSome accessibility issues are not blocking but should be corrected when possible.\n- Non-blocking issues: %d\n\nRule: WCAG 2.2 compliance, per the account's Planetaria validation settings.",
          $nonBlockingCount
        )
        : 'PDF passed validation.';
    }
    else {
      $summaryText = sprintf(
        "PDF did not pass validation.\n\nPlease fix the blocking accessibility issues and re-upload.\n- Blocking issues: %d\n\nRule: WCAG 2.2 compliance, per the account's Planetaria validation settings.",
        $countedIssueCount
      );
    }

    $this->markSummary($file, $passed ? [] : $errors, $summaryText, $documentId);

    return [
      'ok' => TRUE,
      'passed' => $passed,
      'summary' => $summaryText,
      'errors' => $errors,
      'raw' => $document,
      'report_id' => $documentId,
      'report_url' => $this->buildReportUrl($documentId),
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
      $text = $summary ?: 'Planetaria validation failed.';

      if ($reportId !== '') {
        $text .= "\n\nView validation results on Planetaria: " . $this->buildReportUrl($reportId);
      }

      if (!empty($errors)) {
        $text .= "\n\nIssues:\n- " . implode("\n- ", $errors);
      }

      $maxLength = 20000;
      if (strlen($text) > $maxLength) {
        $text = substr($text, 0, $maxLength) . "\n\n[truncated]";
      }

      $file->set('field_pdf_audit_summary', $text);

      // A still-temporary, not-yet-attached file (e.g. the user clicked
      // "Start the PDF compliance check" before saving the media entity)
      // can't be saved directly here without racing the save that attaches
      // it later in the same request; defer via the same tempstore +
      // hook_entity_presave() pattern used for field_pdf_audit_status.
      if (_jcc_pdf_upload_validation_checker_can_save_file_now($file)) {
        $file->save();
      }
      else {
        _jcc_pdf_upload_validation_checker_store_pending_audit_summary_update((int) $file->id(), $text);
      }
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
