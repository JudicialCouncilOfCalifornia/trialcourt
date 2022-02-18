<?php

namespace Drupal\xlsx\Plugin\xlsx\cell;

use Drupal\xlsx\Plugin\XlsxCellBase;

/**
 * Boolean plugin.
 *
 * @XlsxCell(
 *   id = "value_to_boolean",
 *   name = @Translation("Boolean"),
 *   description = @Translation("Convert string value 1, 0 or True/False to boolean value."),
 *   field_types = {
 *     "boolean",
 *   }
 * )
 */
class Boolean extends XlsxCellBase {

  /**
   * {@inheritdoc}
   */
  public function import($entity, $field_name, $value, $mapped_fields) {
    if ($entity->hasField($field_name)) {
      $entity->set($field_name, filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function export($entity, $field_name, $value) {
    if ($entity->hasField($field_name)) {
      if ($entity->get($field_name)->value) {
        return 'TRUE';
      }
      else {
        return '';
      }
    }
  }

}
