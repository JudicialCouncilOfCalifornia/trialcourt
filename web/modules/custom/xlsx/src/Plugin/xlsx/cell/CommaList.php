<?php

namespace Drupal\xlsx\Plugin\xlsx\cell;

use Drupal\xlsx\Plugin\XlsxCellBase;

/**
 * Comma separated list.
 *
 * @XlsxCell(
 *   id = "comma_list",
 *   name = @Translation("Comma list"),
 *   description = @Translation("Comma separated list that would populate multivalue field."),
 *   field_types = {
 *     "list_string",
 *     "list_integer",
 *     "list_float",
 *   }
 * )
 */
class CommaList extends XlsxCellBase {

  /**
   * {@inheritdoc}
   */
  public function import($entity, $field_name, $value, $mapped_fields) {
    if ($entity->hasField($field_name) && !empty($value)) {
      if ($items = explode(',', $value)) {
        $values = [];
        foreach ($items as $item) {
          $values[]['value'] = $item;
        }
        $entity->set($field_name, $values);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function export($entity, $field_name, $value) {
    if ($entity->hasField($field_name) && !empty($value[0]['value'])) {
      $values = [];
      foreach ($value as $item) {
        $values[] = $item['value'];
      }
      return join(',', $values);
    }
  }

}
