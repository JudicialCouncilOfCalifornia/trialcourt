<?php

namespace Drupal\jcc_custom\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\jcc_custom\EventSubscriber\JccMigrationSourceUrlModifier;
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
   * The jobs HTTP request modifier.
   */
  protected JccMigrationSourceUrlModifier $sourceUrlModifier;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->migrationManager = $container->get('plugin.manager.migration');
    $instance->keyValue = $container->get('keyvalue');
    $instance->sourceUrlModifier = $container->get('jcc_custom.migration_source_url_modifier_subscriber');
    $instance->eventDispatcher = $container->get('event_dispatcher');
    $instance->logger = $container->get('logger.factory')->get('jcc_custom');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // The sync option is optional: some callers enqueue a plain import (NULL).
    if (!isset($data['migration_id'])) {
      throw new \InvalidArgumentException('Migration queue item missing required data.');
    }

    $migration_id = $data['migration_id'];
    $sync_option = $data['sync_option'] ?? NULL;
    $migration = $this->migrationManager->createInstance($migration_id);
    if ($migration->getStatus() !== MigrationInterface::STATUS_IDLE) {
      throw new RequeueException(sprintf('Migration %s is busy; its status was preserved.', $migration_id));
    }

    // Preserve Courts' sync policy, but never delete jobs from an empty feed.
    if ($migration_id === 'courts_node_job' && in_array($sync_option, [TRUE, 'sync'], TRUE)) {
      $this->sourceUrlModifier->prepare($migration);
      try {
        if ($migration->getSourcePlugin()->count(TRUE) <= 0) {
          throw new \RuntimeException('Courts jobs source is empty; synchronization cancelled to preserve existing jobs.');
        }
        $migration->getIdMap()->prepareUpdate();
      }
      catch (\Throwable $e) {
        $this->sourceUrlModifier->reset();
        throw $e;
      }
    }

    // Existing opinion callbacks pass TRUE; Granicus updates existing events.
    // NULL runs a plain import of new items.
    if ($sync_option === TRUE || $sync_option === 'sync') {
      // Mirror what is current in the source.
      $migration->set('syncSource', TRUE);
    }
    elseif ($sync_option === 'update') {
      // Reimport all imported items with the new items.
      // Alternatively, use 'track_changes' in YML over this option.
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
      $this->sourceUrlModifier->reset();
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
