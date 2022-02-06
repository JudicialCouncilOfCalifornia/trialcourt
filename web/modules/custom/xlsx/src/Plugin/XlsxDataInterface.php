<?php

namespace Drupal\xlsx\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface XlsxData plugins.
 */
interface XlsxDataInterface extends PluginInspectionInterface {

  /**
   * Return the name of the XLSX cell plugin.
   *
   * @return string
   */
  public function getName();

  /**
   * Return data entity type ID.
   *
   * @return string
   */
  public function getEntityType();

  /**
   * Return depended module name.
   *
   * @return string
   */
  public function getModule();

  /**
   * Query that returns entities for the export.
   *
   * @return query
   */
  public function getEntities($xlsx, $entity_type, $bundle);

}
