<?php

namespace Drupal\xlsx\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the XLSX Entity Mapping entity.
 *
 * @ingroup xlsx
 *
 * @ContentEntityType(
 *   id = "xlsx_entity_mapping",
 *   label = @Translation("Product Download"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *   },
 *   base_table = "xlsx_entity_mapping",
 *   data_table = "xlsx_entity_mapping_field_data",
 *   translatable = FALSE,
 *   admin_permission = "administer xlsx maping",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "entity_type",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   }
 * )
 */
class XlsxEntityMapping extends ContentEntityBase implements XlsxEntityMappingInterface {

  /**
   * XLSX Maping entities are always unpublished.
   */
  public function isPublished() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMapEntityType() {
    return $this->get('entity_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMapEntityType($entity_type, $bundle) {
    $this->set('entity_type', $entity_type . '::' . $bundle);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMapEntityId() {
    return $this->get('entity_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMapEntityId($entity_id) {
    $this->set('entity_id', $entity_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMapHash() {
    return $this->get('hash')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMapHash($hash) {
    $this->set('hash', $hash);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByProperties(array $values = []) {
    return \Drupal::entityTypeManager()->getStorage('xlsx_entity_mapping')->loadByProperties($values);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Type'))
      ->setSettings([
        'max_length' => 512,
        'text_processing' => 0,
      ]);

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity ID'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ]);

    $fields['hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash String'))
      ->setSettings([
        'max_length' => 512,
        'text_processing' => 0,
      ]);

    return $fields;
  }

}
