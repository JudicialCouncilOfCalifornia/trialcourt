<?php

namespace Drupal\xlsx\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines XLSX source annotation object.
 *
 * Plugin Namespace: Plugin\xlsx\Plugin\XlsxSource
 *
 * @see \Drupal\xlsx\Plugin\XlsxSourceManager
 * @see plugin_api
 *
 * @Annotation
 */
class XlsxSource extends Plugin {

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
   * Plugin can run on cron.
   *
   * @var boolean
   */
  public $cron;

}
