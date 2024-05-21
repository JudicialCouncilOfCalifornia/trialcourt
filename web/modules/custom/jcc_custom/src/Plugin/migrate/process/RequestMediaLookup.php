<?php

namespace Drupal\jcc_custom\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Create media entity.
 *
 * @MigrateProcessPlugin(
 *   id = "jcc_request_media_lookup"
 * )
 */
class RequestMediaLookup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($source, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $query = \Drupal::database()->select('migrate_map_' . $this->configuration['migration_id'], 't')
      ->fields('t')
      ->condition('t.sourceid2', $source);

    if (isset($this->configuration['orderby'])) {
      $column = $this->configuration['orderby'];
      $query->addExpression("CAST(t.$column AS SIGNED)", 'delta_as_int');
      $query->orderby('delta_as_int');
    }

    $media_items = $query->execute();
    $items = [];
    foreach ($media_items as $row) {
      if ($row->destid1) {
        $items[] = $row->destid1;
      }
    }

    return $items;
  }

}
