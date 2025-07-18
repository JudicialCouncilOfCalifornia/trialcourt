<?php

namespace Drupal\jcc_tc_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin to safely run preg_replace on values.
 *
 * @MigrateProcessPlugin(
 *   id = "safe_preg_replace"
 * )
 */
class SafePregReplace extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      return $value;
    }

    if (is_array($value)) {
      return array_map([$this, 'transform'], $value, array_fill(0, count($value), $migrate_executable), array_fill(0, count($value), $row), array_fill(0, count($value), $destination_property));
    }

    if (is_string($value)) {
      return preg_replace("/\\{ts\\s+'([^']+)'\\}/", "$1", $value);
    }
    return $value;
  }

}
