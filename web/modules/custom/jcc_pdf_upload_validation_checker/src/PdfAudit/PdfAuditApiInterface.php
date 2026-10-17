<?php

namespace Drupal\jcc_pdf_upload_validation_checker\PdfAudit;

use Drupal\file\FileInterface;

/**
 * Interface for a pluggable PDF accessibility audit API backend.
 *
 * Implementations are registered as services tagged
 * "jcc_pdf_upload_validation_checker.pdf_audit_api" and explicitly listed in
 * the jcc_pdf_upload_validation_checker.pdf_audit_runner service arguments
 * (see jcc_pdf_upload_validation_checker.services.yml), keyed by getId() by
 * PdfAuditRunner. Adding a new API only requires a new implementation of
 * this interface plus a service entry; no other module code needs to change.
 */
interface PdfAuditApiInterface {

  /**
   * Returns the machine-readable id stored in the module's settings config.
   */
  public function getId(): string;

  /**
   * Runs the audit against this API and returns a normalized result array.
   *
   * @return array{
   *   ok: bool,
   *   passed: bool,
   *   summary: string,
   *   errors: string[],
   *   raw: array,
   *   status_code?: int,
   *   report_id?: string,
   *   report_url?: string,
   * }
   *   The normalized audit result. "report_url", when present, is a fully
   *   qualified link to the API's own hosted report and is API-specific;
   *   callers must treat it as an opaque optional link rather than building
   *   report URLs themselves.
   */
  public function audit(FileInterface $file): array;

}
