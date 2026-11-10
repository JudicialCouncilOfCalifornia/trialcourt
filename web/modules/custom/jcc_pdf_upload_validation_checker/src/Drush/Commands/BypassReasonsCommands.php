<?php

namespace Drupal\jcc_pdf_upload_validation_checker\Drush\Commands;

use Drush\Commands\DrushCommands;

/**
 * Drush commands for the JCC PDF upload validation checker module.
 */
class BypassReasonsCommands extends DrushCommands {

  /**
   * Creates/normalizes the 3 canonical Bypass Reasons taxonomy terms.
   *
   * Ensures the "bypass_reasons" vocabulary exists and contains exactly the
   * 3 canonical terms (with descriptions), renaming/updating any terms
   * previously created under an older name so nothing is duplicated.
   *
   * Unlike a numbered hook_update_N(), this does not depend on this
   * module's recorded schema version, so it's safe to run post-deployment
   * on any site regardless of its update-hook history/drift.
   *
   * @command jcc-pdf-checker:seed-bypass-reasons
   * @aliases jcc-pdf-seed
   */
  public function seedBypassReasons(): void {
    \Drupal::moduleHandler()->loadInclude('jcc_pdf_upload_validation_checker', 'install');
    _jcc_pdf_upload_validation_checker_ensure_bypass_reason_vocabulary();
    $this->logger()->success(dt('Bypass Reasons vocabulary and canonical terms are up to date.'));
  }

}
