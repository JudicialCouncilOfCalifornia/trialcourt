<?php

namespace Drupal\jcc_tc_migration\Plugin\migrate\process;

use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a sanitize_filename plugin.
 *
 * Returns the path portion of a url.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: sanitize_filename
 *     source: filename
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "sanitize_filename"
 * )
 */
class SanitizeFilename extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {

    if (empty($value)) {
      return $value;
    }

    $transliteration = \Drupal::service('transliteration');
    $filename = $transliteration->transliterate($value);
    // Replace whitespace.
    $filename = str_replace(' ', '-', $filename);
    // Remove remaining unsafe characters.
    $filename = preg_replace('![^0-9A-Za-z_.-]!', '', $filename);
    // Remove multiple consecutive non-alphabetical characters.
    $filename = preg_replace('/(_)_+|(\.)\.+|(-)-+/', '\\1\\2\\3', $filename);
    // Force lowercase to prevent issues on case-insensitive file systems.
    $filename = strtolower($filename);

    return $filename;
  }

}
