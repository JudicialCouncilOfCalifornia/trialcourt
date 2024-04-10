<?php

namespace Drupal\jcc_custom\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Creates filename ids to link to opinion nodes.
 *
 * @MigrateProcessPlugin(
 *   id = "jcc_opinion_files"
 * )
 *
 * To do custom value transformations use the following.
 *
 * @code
 * field_link:
 *   plugin: jcc_opinion_files
 *   source: string
 * @endcode
 */
class JccOpinionFiles extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($value) {
      $files = [
        0 => $value . '.PDF',
        1 => $value . '.DOC',
      ];
      return $files;
    }
  }

}
