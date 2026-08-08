<?php

namespace Drupal\jcc_custom\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Processes scheduled migration tasks with lease time set in callback.
 *
 * @QueueWorker(
 *   id = "migration_queue_worker",
 *   title = @Translation("Migration Queue Worker"),
 *   cron = {"time" = 60}
 * )
 */
class MigrationQueueWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!isset($data['migration_id']) || !isset($data['sync_option'])) {
      \Drupal::logger('jcc_custom')->error('Opinions sync queue item missing required data.');
      return;
    }

    $sync_option = $data['sync_option'];
    $migration_id = $data['migration_id'];
    $migration_manager = \Drupal::service('plugin.manager.migration');
    $migration = $migration_manager->createInstance($migration_id);

    // 1. Stop & reset migration as a precaution.
    if ($migration->getStatus() !== MigrationInterface::RESULT_STOPPED) {
      $migration->setStatus(MigrationInterface::RESULT_STOPPED);
    }
    if ($migration->getStatus() !== MigrationInterface::STATUS_IDLE) {
      $migration->setStatus(MigrationInterface::STATUS_IDLE);
    }

    // 2. Set options.
    // Mirror what is current in the source.
    switch ($sync_option) {
      case 'sync':
        $migration->set('syncSource', TRUE);
        break;
    }

    // 3. Execute the migration.
    $message = new MigrateMessage();
    $executable = new MigrateExecutable($migration, $message);
    try {
      $executable->import();

      // Update the migrate_last_imported key-value store.
      $store = \Drupal::keyValue('migrate_last_imported');
      $timestamp = time() * 1000;
      $store->set($migration_id, $timestamp);
    }
    catch (\Exception $e) {
      \Drupal::logger('jcc_custom')->error('Migration sync failed: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
