<?php

namespace Drupal\jcc_tc_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a prefix_relative_links plugin.
 *
 * Will add a leading slash to relative link paths where needed.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: prefix_relative_links
 *     source: html
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "prefix_relative_links"
 * )
 */
class PrefixRelativeLinks extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {

    if (!empty($value)) {
      $value = \Drupal::service('jcc_tc_migration.prefix_relative_links')->replace($value);
    }

    return $value;
  }

}
