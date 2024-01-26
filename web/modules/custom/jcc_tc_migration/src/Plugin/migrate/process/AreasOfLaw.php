<?php

namespace Drupal\jcc_tc_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides an Empty Coalesce process plugin.
 *
 * Given a set of values provided to the plugin, the plugin will return the
 * first non-empty value.
 *
 * Available configuration keys:
 * - source: The input array.
 *
 * Example:
 * Given source keys of foo, bar, and baz:
 *
 * process_key:
 *   plugin: empty_coalesce
 *   source:
 *     - foo
 *     - bar
 *     - baz
 *
 * @MigrateProcessPlugin(
 *   id = "areas_of_law"
 * )
 */
class AreasOfLaw extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException("The input value should be an array.");
    }
    $value = array_flip(array_unique($value));
    unset($value['']);

    return array_keys($value);
  }

}
