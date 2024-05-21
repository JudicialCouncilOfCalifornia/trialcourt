<?php

namespace Drupal\jcc_custom\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Create media entity.
 *
 * @MigrateProcessPlugin(
 *   id = "jcc_request_paragraph_lookup"
 * )
 */
class RequestParagraphLookup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($source, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $migration_id = $this->configuration['migration_id'];

    $paragraphs = \Drupal::database()->select('migrate_map_' . $migration_id, 't')
      ->fields('t', ['destid1', 'destid2'])
      ->condition('t.sourceid2', $source)
      ->execute();

    $items = [];
    foreach ($paragraphs as $row) {
      $items[] = [
        'target_id' => $row->destid1,
        'target_revision_id' => $row->destid2,
      ];
    }

    return $items;
  }

}
