<?php

namespace Drupal\xlsx\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines XLSX export annotation object.
 *
 * Plugin Namespace: Plugin\xlsx\Plugin\XlsxExport
 *
 * @see \Drupal\xlsx\Plugin\XlsxExportManager
 * @see plugin_api
 *
 * @Annotation
 */
class XlsxExport extends Plugin {

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
   * Supported sources.
   *
   * @var array
   */
  public $source_types = [];

  /**
   * Export file data type.
   *
   * @var string
   */
  public $data_type;

}
