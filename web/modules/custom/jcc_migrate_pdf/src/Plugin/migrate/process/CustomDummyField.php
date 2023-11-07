<?php

namespace Drupal\jcc_migrate_pdf\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Provides a process plugin to set a dummy value for a field.
 *
 * @MigrateProcessPlugin(
 *   id = "custom_dummy_field"
 * )
 */
class CustomDummyField extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Replace 'your_dummy_value' with the value you want to set.
    // dd("hi"); 
    return 'test_value';
  }

}
