<?php

namespace Drupal\your_module\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a 'CustomAMPMProcessPlugin' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "your_custom_process_plugin"
 * )
 * */

class RemoteHearing extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */

  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
  $direction = $this->configuration['direction'];
  $subject = $row->getSourceProperty('Subject');

  if (stripos($subject, 'AM') !== FALSE && $direction == 'am') {
    return $value;
  }
  elseif (stripos($subject, 'PM') !== FALSE && $direction == 'pm') {
    return $value;
  }
  elseif (stripos($subject, 'AM') === FALSE && stripos($subject, 'PM') === FALSE) {
    return $value;
  }

  return NULL;
}

}
