<?php

namespace Drupal\xlsx\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes Import Tasks.
 *
 * @QueueWorker(
 *   id = "xlsx_import_queue_processor",
 *   title = @Translation("Task Worker: XLSX Import"),
 *   cron = {"time" = 180}
 * )
 */
class ImportQueueProcessor extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($items) {
    $context = [];
    \Drupal::service('xlsx.batch_ops')->import($items[0], $items[1], $items[2], $items[3], $items[4], $context);
  }

}
