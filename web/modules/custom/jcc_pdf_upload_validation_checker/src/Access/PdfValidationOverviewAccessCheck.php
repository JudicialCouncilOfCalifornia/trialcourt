<?php

namespace Drupal\jcc_pdf_upload_validation_checker\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Access check for the PDF Validation overview report page/menu link.
 */
class PdfValidationOverviewAccessCheck {

  /**
   * Denies access when the module's master "Enable" switch is off.
   *
   * This is combined (AND) with the view's own permission-based access
   * check, so the page/menu link is only reachable when a user both has
   * the "access pdf validation overview" permission and the feature is
   * enabled at /admin/config/system/jcc-pdf-upload-validation-checker.
   */
  public function access(AccountInterface $account): AccessResult {
    $enabled = (bool) \Drupal::config('jcc_pdf_upload_validation_checker.settings')->get('enabled');

    return AccessResult::allowedIf($enabled)
      // Evaluate dynamically so page/menu visibility tracks config toggles instantly.
      ->setCacheMaxAge(0);
  }

}
