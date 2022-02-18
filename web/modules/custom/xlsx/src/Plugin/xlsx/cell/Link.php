<?php

namespace Drupal\xlsx\Plugin\xlsx\cell;

use Drupal\xlsx\Plugin\XlsxCellBase;

/**
 * Default XLSX cell plugin.
 *
 * @XlsxCell(
 *   id = "link",
 *   name = @Translation("Link"),
 *   description = @Translation("Process Link fields"),
 *   field_types = {
 *     "link",
 *   }
 * )
 */
class Link extends XlsxCellBase {

  /**
   * {@inheritdoc}
   */
  public function export($entity, $field_name, $value) {
    if ($entity->hasField($field_name)) {
      if ($value[0]['uri']) {
        return $value[0]['uri'];
      }
    }
  }

}
