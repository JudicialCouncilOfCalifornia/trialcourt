<?php

namespace Drupal\xlsx\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines XLSX data annotation object.
 *
 * Plugin Namespace: Plugin\xlsx\Plugin\XlsxData
 *
 * @see \Drupal\xlsx\Plugin\XlsxDataManager
 * @see plugin_api
 *
 * @Annotation
 */
class XlsxData extends Plugin {

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
   * Entity type machine name (eg. node, user, file etc.)
   *
   * @var string
   */
  public $entity_type;

  /**
   * Module name.
   *
   * @var string
   */
  public $module;

}
