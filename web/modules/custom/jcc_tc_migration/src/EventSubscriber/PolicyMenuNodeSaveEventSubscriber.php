<?php

declare(strict_types = 1);

namespace Drupal\jcc_tc_migration\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Resave the node to update the breadcrumb.
 *
 * @package Drupal\jcc_tc_migration\EventSubscriber
 */
class PolicyMenuNodeSaveEventSubscriber implements EventSubscriberInterface {

  /**
   * Database connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * PolicyMenuNodeSaveEventSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * Resave the node to update the breadcrumb.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The migrate post row save event.
   */
  public function onPostRowSave(MigratePostRowSaveEvent $event) : void {
    if ($event->getMigration()->getBaseId() == 'policy_subpage_node_menu_parent') {
      $id = $event->getDestinationIdValues();
      $id = reset($id);

      // Get the node from the menu data.
      $query = $this->database->select('menu_link_content_data', 'lt');
      $query->fields('lt', ['link__uri'])
        ->condition('id', $id);
      $result = $query->execute();
      $menu_link = $result->fetchField();

      if (!empty($menu_link) && str_starts_with($menu_link, "entity:node/")) {
        $node_id = str_replace("entity:node/", "", $menu_link);

        // Save the node to update breadcrumb.
        if ($node_id) {
          $node = $this->entityTypeManager->getStorage('node')->load($node_id);
          $node->save();
        }
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    $events = [];
    $events[MigrateEvents::POST_ROW_SAVE] = ['onPostRowSave'];
    return $events;
  }

}
