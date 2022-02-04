<?php

namespace Drupal\xlsx\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class XlsxEntityEvent.
 *
 * @package xlsx
 */
class XlsxEntityEvent extends Event {

  /**
   * The Entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * The event type.
   */
  private $eventType;

  /**
   * Construct XlsxEntityEvent event.
   */
  public function __construct($event_type, EntityInterface $entity) {
    $this->eventType = $event_type;
    $this->entity = $entity;
  }

  /**
   * Get entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Get event type.
   */
  public function getEventType() {
    return $this->eventType;
  }

}
