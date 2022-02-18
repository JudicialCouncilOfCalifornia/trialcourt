<?php

namespace Drupal\xlsx\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\xlsx\Event\XlsxEventType;
use Drupal\xlsx\Event\XlsxEntityEvent;

/**
 * Class XlsxEventsSubscriber.
 *
 * @package xlsx
 */
class XlsxEventsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      XlsxEventType::UPDATE_ENTITY => 'updateEntity',
      XlsxEventType::DELETE_ENTITY => 'deleteEntity',
    ];
  }

  /**
   * React to an entity being updated.
   *
   * @param \Drupal\xlsx\Event\XlsxEntityEvent $event
   *   Entity event.
   */
  public function updateEntity(XlsxEntityEvent $event) {
    if ($xlsx_data = $this->loadXlsxDataEntity($event->getEntity())) {
      // We just update changed timestamp so we know Drupal probably contains the most recent content.
      $xlsx_data->setChangedTime(\Drupal::time()->getCurrentTime());
      $xlsx_data->save();
    }
  }

  /**
   * React to an entity being deleted.
   *
   * @param \Drupal\xlsx\Event\XlsxEntityEvent $event
   *   Entity event.
   */
  public function deleteEntity(XlsxEntityEvent $event) {
    if ($xlsx_data = $this->loadXlsxDataEntity($event->getEntity())) {
      $xlsx_data->delete();
    }
  }

  /**
   * Load XLSX data entity.
   */
  protected function loadXlsxDataEntity($entity) {
    $entities = \Drupal::entityTypeManager()->getStorage('xlsx_data')->loadByProperties([
      'entity_type_id' => $entity->getEntityTypeId(),
      'bundle' => $entity->bundle(),
      'entity_id' => $entity->id(),
    ]);
    if ($xlsx_data = reset($entities)) {
      return $xlsx_data;
    }
  }

}
