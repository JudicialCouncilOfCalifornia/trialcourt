<?php

namespace Drupal\jcc_custom\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Falls back to known event titles when Granicus omits the event type.
 *
 * @MigrateProcessPlugin(
 *   id = "jcc_granicus_event_type"
 * )
 */
class GranicusEventType extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    [$type, $title] = $value;
    if (trim((string) $type) !== '') {
      return $type;
    }
    $known_types = [
      'Supreme Court Oral Argument',
      'Judicial Council Meeting',
      'Commission on Judicial Appointments',
    ];
    foreach ($known_types as $known_type) {
      if (stripos((string) $title, $known_type) !== FALSE) {
        return $known_type;
      }
    }
    // Unknown events remain subject to the migration's existing row filter.
    return '';
  }

}
