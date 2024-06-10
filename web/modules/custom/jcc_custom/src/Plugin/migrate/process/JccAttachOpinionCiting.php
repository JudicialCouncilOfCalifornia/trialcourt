<?php

namespace Drupal\jcc_custom\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Converts finds and attaches citing media to opinion.
 *
 * @MigrateProcessPlugin(
 *   id = "jcc_attach_opinion_citing"
 * )
 *
 * To do custom value transformations use the following.
 *
 * @code
 * field_link:
 *   plugin: jcc_attach_opinion_citing
 *   source: string
 * @endcode
 */
class JccAttachOpinionCiting extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $media_ids = '';
    if ($value) {
      $query = \Drupal::entityQuery('media');
      $query->condition('status', 1);
      $query->condition('name', $value, 'CONTAINS');
      $media_ids = implode(', ', $query->execute());
    }
    return $media_ids;
  }

}
