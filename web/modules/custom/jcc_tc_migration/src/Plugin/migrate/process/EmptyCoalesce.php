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
 * - default_value: (optional) The value to return if all values are NULL.
 *   if not provided, NULL is returned if all values are NULL.
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
 * This plugin will return the equivalent of `foo ?? bar ?? baz`
 *
 * @MigrateProcessPlugin(
 *   id = "empty_coalesce"
 * )
 */
class EmptyCoalesce extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException("The input value should be an array.");
    }
    foreach ($value as $val) {
      $val = trim($val);
      if (!empty($val)) {
        return $val;
      }
    }
    if (isset($this->configuration['default_value'])) {
      return $this->configuration['default_value'];
    }
    return NULL;
  }

}
