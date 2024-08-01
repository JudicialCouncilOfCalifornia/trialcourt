<?php

namespace Drupal\jcc_elevated_rfp_solicitations\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a plugin to replace links with media links in bodies.
 *
 * Also applies a pathauto cleaner service to the file name to improve matching.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: jcc_solicitations_media_replace_file_link
 *     source: html
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "jcc_solicitations_media_replace_file_link"
 * )
 */
class JccSolicitationMediaReplaceFileLink extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {

    if (!empty($value)) {
      $value = \Drupal::service('jcc_elevated_rfp_solicitations.media_replace_file_link')->replace($value);
    }

    return $value;
  }

}
