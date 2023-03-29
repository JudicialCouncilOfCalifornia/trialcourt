<?php

namespace Drupal\xlsx;

use Drupal\Core\Queue\QueueFactory;
use Drupal\xlsx\Plugin\XlsxSourceManager;

/**
 * Xlsx Cron Imports.
 *
 * @ingroup xlsx
 */
class CronImports {

  /**
   * @var \Drupal\xlsx\Plugin\XlsxSourceManager
   */
  protected $xlsxSourceManager;

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * Constructs a new CronImports object.
   */
  public function __construct(XlsxSourceManager $xlsx_source, QueueFactory $queue) {
    $this->xlsxSourceManager = $xlsx_source;
    $this->queue = $queue;
  }

  /**
   * Create and process XLSX import queues.
   */
  public function processQueue() {
    $storage = \Drupal::entityTypeManager()->getStorage('xlsx');
    $xlsx_entities = $storage->loadMultiple();

    foreach ($xlsx_entities as $xlsx) {
      $xlsx_source = $this->xlsxSourceManager->createInstance($xlsx->getSourcePlugin());
      // @TODO: Add rountine to make sure this runs only once per set time.
      if ($xlsx->shouldExecuteOnCron()) {
        $data = $xlsx->getMapping();
        if (!empty($data['file_path'])) {
          // This only works with CSV files for now.
          if ($batch_items = $xlsx_source->buildBatchItemsFilePath($xlsx, $data['file_path'], 'Csv')) {

            $purge_queue = $this->queue->get('xlsx_purge_queue_processor');
            // Purge data
            if ($entity_ids = $this->loadEntitiesByMapping($xlsx->id())) {
              foreach (array_chunk($entity_ids, 100) as $ids) {
                $purge_queue->createItem([$ids]);
              }
            }
            $import_queue = $this->queue->get('xlsx_import_queue_processor');
            foreach ($batch_items as $item) {
              $import_queue->createItem($item[1]);
            }
            $xlsx->setLastCron();
            $xlsx->setLastImport();
          }
        }
      }
    }
  }

  /**
   * Load entity by entity type and mapping ID.
   */
  protected function loadEntitiesByMapping($mapping_id) {
    $result = \Drupal::entityQuery('xlsx_data')->condition('mapping_id', $mapping_id)->execute();
    if ($ids = array_values($result)) {
      return $ids;
    }
  }

}
