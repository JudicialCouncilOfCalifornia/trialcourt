<?php

namespace Drupal\xlsx\Plugin\xlsx\data;

use Drupal\xlsx\Plugin\XlsxDataBase;

/**
 * Node entities.
 *
 * @XlsxData(
 *   id = "node",
 *   name = @Translation("Default"),
 *   entity_type = "node",
 *   module = "node"
 * )
 */
class Node extends XlsxDataBase {

  /**
   * {@inheritdoc}
   */
  public function getEntities($xlsx, $entity_type, $bundle) {
    return $this->entityTypeManager->getStorage($entity_type)->loadByProperties(['type' => $bundle]);
  }

}
