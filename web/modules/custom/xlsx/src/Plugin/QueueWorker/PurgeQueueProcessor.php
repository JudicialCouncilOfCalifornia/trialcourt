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
  public function processItem($data) {
    list($ids) = $data;
    $storage = \Drupal::entityTypeManager()->getStorage('xlsx_data');
    $entities = $storage->loadMultiple($ids);
    if ($entities) {
      $first_entity = current($entities);
      $imported_storage = \Drupal::entityTypeManager()->getStorage($first_entity->get('entity_type_id')->value, $first_entity->get('bundle')->value);
      $imported_ids = [];
      foreach ($entities as $entity) {
        $imported_ids[] = $entity->get('entity_id')->value;
      }
      if (!empty($imported_ids)) {
        $imported_entities = $imported_storage->loadMultiple($imported_ids);
        $imported_storage->delete($imported_entities);
      }
      $storage->delete($entities);
    }
  }

}
