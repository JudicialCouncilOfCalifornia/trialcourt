<?php

namespace Drupal\xlsx\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines XLSX cell process annotation object.
 *
 * Plugin Namespace: Plugin\xlsx\Plugin\XlsxCell
 *
 * @see \Drupal\xlsx\Plugin\XlsxCellManager
 * @see plugin_api
 *
 * @Annotation
 */
class XlsxCell extends Plugin {

  /**
   * Plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * Plugin name.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * Plugin description.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * A list of supported field types.
   *
   * @var array
   */
  public $field_types = [];

}
