<?php

namespace Drupal\jcc_custom;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\RequeueException;

/**
 * Defines the MigrationJobService class.
 */
class MigrationJobService {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    protected QueueFactory $queueFactory,
    protected PluginManagerInterface $queueWorkerManager,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Common migration function.
   */
  public function jccRunMigration($migration_id, $sync_option = FALSE, $lease_time = 3600) {
    $queue = $this->queueFactory->get('migration_queue_worker');
    $queue_worker = $this->queueWorkerManager->createInstance('migration_queue_worker');

    // Create queue item.
    $data = [
      'migration_id' => $migration_id,
      'sync_option' => $sync_option,
    ];
    $queue->createItem($data);

    // Queue item managment.
    while ($item = $queue->claimItem($lease_time)) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
      }
      catch (RequeueException $e) {
        // Item will be requeued automatically.
        $queue->releaseItem($item);
      }
      catch (\Exception $e) {
        // Critical failure - remove item and log error.
        $this->loggerFactory->get('jcc_custom')->error('Queue processing failed: @message', [
          '@message' => $e->getMessage(),
        ]);
        $queue->deleteItem($item);
      }
    }
  }

}
