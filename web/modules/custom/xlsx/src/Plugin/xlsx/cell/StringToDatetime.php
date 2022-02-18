<?php

namespace Drupal\xlsx\Plugin\xlsx\cell;

use Drupal\xlsx\Plugin\XlsxCellBase;

/**
 * String to Datetime plugin.
 *
 * @XlsxCell(
 *   id = "str_to_datetime",
 *   name = @Translation("String to Datetime"),
 *   description = @Translation("Convert string value to datetime."),
 *   field_types = {
 *     "datetime",
 *   }
 * )
 */
class StringToDatetime extends XlsxCellBase {

  /**
   * {@inheritdoc}
   */
  public function import($entity, $field_name, $value, $mapped_fields) {
    if ($entity->hasField($field_name)) {
      // 2022-01-22T21:22:35
      $entity->set($field_name, date('Y-m-d\TH:i:s', strtotime($value)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function export($entity, $field_name, $value) {
    if ($entity->hasField($field_name)) {
      return $entity->get($field_name)->value;
    }
  }

}
