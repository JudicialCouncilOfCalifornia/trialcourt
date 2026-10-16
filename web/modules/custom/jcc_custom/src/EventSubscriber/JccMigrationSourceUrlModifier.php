<?php

namespace Drupal\jcc_custom\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber to modify migration source URLs before import.
 */
class JccMigrationSourceUrlModifier implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MigrateEvents::PRE_IMPORT => ['onPreImport'],
    ];
  }

  /**
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs logging for the migration source URL modifier.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->logger = $logger_factory->get('jcc_migration_source_url_modifier');
  }

  /**
   * Management for modifying migration source URLs.
   *
   * Not used by Migrate Source UI but that migrate option is broken.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The event to process.
   */
  public function onPreImport(MigrateImportEvent $event) {
    $migration = $event->getMigration();

    switch ($migration->id()) {
      case 'courts_node_job':
      case 'appellate_node_job':
        $source_configuration = $migration->getSourceConfiguration();

        if (!empty($source_configuration['urls'])) {
          $urls = (array) $source_configuration['urls'];

          foreach ($urls as &$url) {
            // Append the current Unix timestamp.
            $separator = (strpos($url, '?') === FALSE) ? '?' : '&';
            $url .= $separator . 'timestamp=' . time();
            // Log timestamped URL to confirm it has been correctly modified.
            $this->logger->info('Timestamped URL for migration @migration: @url', [
              '@migration' => $migration->id(),
              '@url' => $url,
            ]);
          }

          $source_configuration['urls'] = $urls;
          $migration->set('source', $source_configuration);
        }
        break;
    }
  }

}
