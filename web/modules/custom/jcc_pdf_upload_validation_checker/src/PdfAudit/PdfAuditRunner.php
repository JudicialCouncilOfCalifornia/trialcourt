<?php

namespace Drupal\jcc_pdf_upload_validation_checker\PdfAudit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\file\FileInterface;
use Drupal\jcc_pdf_upload_validation_checker\PdfAudit\Api\DefaultPdfAuditApi;
use Drupal\jcc_pdf_upload_validation_checker\PdfAudit\Api\EqualWebPdfAuditApi;

/**
 * Dispatches PDF accessibility audits to the configured API backend.
 *
 * Available backends are injected as an explicit service list in
 * jcc_pdf_upload_validation_checker.services.yml (this site's Drupal/Yaml
 * setup does not support the "!tagged_iterator" custom YAML tag). To add a
 * new API: implement PdfAuditApiInterface, register it as a tagged service,
 * add it to the pdf_audit_runner argument list, and add an option to
 * SettingsForm — this class does not need to change.
 */
final class PdfAuditRunner {

  /**
   * Available audit APIs, keyed by PdfAuditApiInterface::getId().
   *
   * @var \Drupal\jcc_pdf_upload_validation_checker\PdfAudit\PdfAuditApiInterface[]
   */
  private array $apis = [];

  /**
   * Constructs the PdfAuditRunner service.
   *
   * @param iterable<\Drupal\jcc_pdf_upload_validation_checker\PdfAudit\PdfAuditApiInterface> $apis
   *   The registered PDF audit API backends.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory used to retrieve module settings.
   */
  public function __construct(
    iterable $apis,
    private ConfigFactoryInterface $configFactory,
  ) {
    foreach ($apis as $api) {
      $this->apis[$api->getId()] = $api;
    }
  }

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
    $configuredId = $this->normalizeApiId((string) ($config->get('pdf_validation_api') ?? DefaultPdfAuditApi::ID));

    $api = $this->apis[$configuredId] ?? $this->apis[DefaultPdfAuditApi::ID] ?? reset($this->apis);

    if (!$api instanceof PdfAuditApiInterface) {
      return [
        'ok' => FALSE,
        'passed' => FALSE,
        'summary' => 'No PDF audit API is configured.',
        'errors' => ['No PDF audit API backends are registered.'],
        'raw' => [],
      ];
    }

    return $api->audit($file);
  }

  /**
   * Normalizes legacy/human-readable stored config values to a machine id.
   *
   * Older settings were saved using the select option's label text
   * ("PDF audit", "EqualWeb") rather than a clean machine name; accept
   * those transparently so existing site config keeps working.
   */
  private function normalizeApiId(string $rawId): string {
    $lower = strtolower(trim($rawId));

    return match (TRUE) {
      in_array($lower, ['equalweb', 'equal_web'], TRUE) => EqualWebPdfAuditApi::ID,
      in_array($lower, ['pdf audit', 'pdf_audit'], TRUE) => DefaultPdfAuditApi::ID,
      default => $lower,
    };
  }

}
