<?php

namespace Drupal\jcc_custom\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateMapSaveEvent;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes migrations without hiding failures or resetting busy jobs.
 *
 * @QueueWorker(
 *   id = "migration_queue_worker",
 *   title = @Translation("Migration Queue Worker"),
 *   cron = {"time" = 60}
 * )
 */
final class MigrationQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationManager;

  /**
   * The key value factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValue;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The migration logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->migrationManager = $container->get('plugin.manager.migration');
    $instance->keyValue = $container->get('keyvalue');
    $instance->eventDispatcher = $container->get('event_dispatcher');
    $instance->logger = $container->get('logger.factory')->get('jcc_custom');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!isset($data['migration_id'], $data['sync_option'])) {
      throw new \InvalidArgumentException('Migration queue item missing required data.');
    }

    $migration_id = $data['migration_id'];
    $migration = $this->migrationManager->createInstance($migration_id);
    if ($migration->getStatus() !== MigrationInterface::STATUS_IDLE) {
      throw new RequeueException(sprintf('Migration %s is busy; its status was preserved.', $migration_id));
    }

    // Existing opinion callbacks pass TRUE; Granicus updates existing events.
    if ($data['sync_option'] === TRUE || $data['sync_option'] === 'sync') {
      $migration->set('syncSource', TRUE);
    }
    elseif ($data['sync_option'] === 'update') {
      $migration->getIdMap()->prepareUpdate();
    }

    $counts = ['imported' => 0, 'failed' => 0, 'ignored' => 0];
    $listener = static function (MigrateMapSaveEvent $event) use ($migration, &$counts) {
      if ($event->getMap() !== $migration->getIdMap()) {
        return;
      }
      $status = $event->getFields()['source_row_status'];
      $key = match ($status) {
        MigrateIdMapInterface::STATUS_FAILED => 'failed',
        MigrateIdMapInterface::STATUS_IGNORED => 'ignored',
        default => 'imported',
      };
      $counts[$key]++;
    };
    $this->eventDispatcher->addListener(MigrateEvents::MAP_SAVE, $listener);
    try {
      $executable = new MigrateExecutable($migration, new MigrateMessage(), $this->eventDispatcher);
      $result = $executable->import();
    }
    finally {
      $this->eventDispatcher->removeListener(MigrateEvents::MAP_SAVE, $listener);
    }

    if ($result !== MigrationInterface::RESULT_COMPLETED || $counts['failed'] > 0) {
      throw new \RuntimeException(sprintf('Migration %s failed or did not complete (result %s, %d failed rows). See migration messages.', $migration_id, $result, $counts['failed']));
    }
    $this->keyValue->get('migrate_last_imported')->set($migration_id, time() * 1000);
    $this->logger->notice('Migration @id completed: @imported imported or updated, @ignored ignored, @failed failed.', [
      '@id' => $migration_id,
      '@imported' => $counts['imported'],
      '@ignored' => $counts['ignored'],
      '@failed' => $counts['failed'],
    ]);
  }

}
