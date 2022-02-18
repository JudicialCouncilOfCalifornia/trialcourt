<?php

namespace Drupal\xlsx\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * XlsxCell plugin base class.
 *
 * @package xlsx
 */
class XlsxCellBase extends PluginBase implements XlsxCellInterface {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTypes() {
    return $this->pluginDefinition['field_types'];
  }

  /**
   * {@inheritdoc}
   */
  public function import($entity, $field_name, $value, $mapped_fields) {
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function export($entity, $field_name, $value) {
    $entity_type = $entity->getEntityType();
    if ($entity_type->id() == 'webform_submission') {
      return !empty($value) ? $value : '';
    }
    else {
      return !empty($value[0]['value']) ? $value[0]['value'] : '';
    }
  }

}
