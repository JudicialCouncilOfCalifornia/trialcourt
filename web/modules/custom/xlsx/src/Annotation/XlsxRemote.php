<?php

namespace Drupal\xlsx\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines XLSX remote annotation object.
 *
 * Plugin Namespace: Plugin\xlsx\Plugin\XlsxRemote
 *
 * @see \Drupal\xlsx\Plugin\XlsxRemotelManager
 * @see plugin_api
 *
 * @Annotation
 */
class XlsxRemote extends Plugin {

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

}
