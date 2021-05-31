<?php

namespace Drupal\jcc_tc_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a media_replace_file_link plugin.
 *
 * Will search and replace file links with equivelant media entity links if
 * available.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: media_replace_file_link
 *     source: html
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "media_replace_file_link"
 * )
 */
class MediaReplaceFileLink extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {

    $value = \Drupal::service('jcc_tc_migration.media_replace_file_link')->replace($value);

    return $value;
  }

}
