<?php

namespace Drupal\xlsx\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining XLSX Entity Mapping entities.
 *
 * @ingroup xlsx
 */
interface XlsxEntityMappingInterface extends ContentEntityInterface {

  /**
   * Get entity type ID.
   */
  public function getMapEntityType();

  /**
   * Set entity type (type id and bundle).
   */
  public function setMapEntityType($entity_type, $bundle);

  /**
   * Get entity ID.
   */
  public function getMapEntityId();

  /**
   * Set entity ID.
   */
  public function setMapEntityId($entity_id);

  /**
   * Get string hash.
   */
  public function getMapHash();

  /**
   * Set string hash.
   */
  public function setMapHash($hash);

}
