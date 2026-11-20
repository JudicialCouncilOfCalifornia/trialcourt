<?php

namespace Drupal\jcc_custom;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;

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
  public function jccRunMigration($migration_id, $sync_option = NULL, $lease_time = 3600) {
    $queue = $this->queueFactory->get('migration_queue_worker');
    $queue_worker = $this->queueWorkerManager->createInstance('migration_queue_worker');

    // Drain pending work first so a failing job does not enqueue duplicates.
    $data = [
      'migration_id' => $migration_id,
      'sync_option' => $sync_option,
    ];
    $remaining = $queue->numberOfItems() + 1;
    $requested_processed = FALSE;
    $enqueued = FALSE;
    $failed_items = [];
    $failure = NULL;
    try {
      while ($remaining-- > 0) {
        $item = $queue->claimItem($lease_time);
        if (!$item) {
          if ($requested_processed || $enqueued) {
            break;
          }
          $queue->createItem($data);
          $enqueued = TRUE;
          $item = $queue->claimItem($lease_time);
          if (!$item) {
            throw new \RuntimeException('Migration queue item could not be claimed.');
          }
        }
        $requested_processed = $requested_processed || ($item->data['migration_id'] ?? NULL) === $migration_id;
        try {
          $queue_worker->processItem($item->data);
          $queue->deleteItem($item);
        }
        catch (\Throwable $e) {
          // Keep failures leased until the pass ends so other jobs can run.
          $failed_items[] = $item;
          $failure ??= $e;
          $this->loggerFactory->get('jcc_custom')->error('Queue processing failed: @message', [
            '@message' => $e->getMessage(),
          ]);
        }
      }
    }
    finally {
      foreach ($failed_items as $item) {
        $queue->releaseItem($item);
      }
    }
    if ($failure) {
      // Preserve the failed work and let Ultimate Cron report the failure.
      throw $failure;
    }
  }

}
