<?php

namespace Drupal\xlsx\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes Purge Tasks.
 *
 * @QueueWorker(
 *   id = "xlsx_purge_queue_processor",
 *   title = @Translation("Task Worker: XLSX Purge"),
 *   cron = {"time" = 180}
 * )
 */
class PurgeQueueProcessor extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($entity) {
    if ($entity) {
      $imported_entity = \Drupal::entityTypeManager()
        ->getStorage($entity->get('entity_type_id')->value, $entity->get('bundle')->value)
        ->load($entity->get('entity_id')->value);
      if ($imported_entity) {
        $imported_entity->delete();
      }
      $entity->delete();
    }
  }

}
