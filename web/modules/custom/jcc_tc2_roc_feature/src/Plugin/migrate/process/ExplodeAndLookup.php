<?php

namespace Drupal\jcc_tc2_roc_feature\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Create media entity.
 *
 * @MigrateProcessPlugin(
 *   id = "jcc_explode_lookup"
 * )
 */
class ExplodeAndLookup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($source, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $titles = explode('::', $source);
    $items = [];

    if (!empty($titles)) {
      foreach ($titles as $title) {
        $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['title' => $title]);
        if ($node = reset($nodes)) {
          $items[] = ['target_id' => $node->id()];
        }
      }
    }

    return $items;
  }

}
