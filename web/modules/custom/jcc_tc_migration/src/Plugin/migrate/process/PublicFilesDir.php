<?php

namespace Drupal\jcc_tc_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a public_files_dir plugin.
 *
 * Returns the drupal public files directory.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: public_files_dir
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "public_files_dir"
 * )
 */
class PublicFilesDir extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {

    if ($wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri('public://')) {
      $external = $wrapper->getExternalUrl();
      $path = parse_url($external, PHP_URL_PATH);
    }
    return $path;
  }

}
