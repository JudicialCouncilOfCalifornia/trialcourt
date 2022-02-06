<?php

namespace Drupal\xlsx\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface XlsxCell plugins.
 */
interface XlsxCellInterface extends PluginInspectionInterface {

  /**
   * Return the name of the XLSX cell plugin.
   *
   * @return string
   */
  public function getName();

  /**
   * Return description for the XLSX cell plugin.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Return a list of supported field types.
   *
   * @return array
   */
  public function getFieldTypes();

  /**
   * Process cell value for import.
   *
   * @return mixed
   */
  public function import($entity, $field_name, $value, $mapped_fields);

  /**
   * Process data for XLSX cell.
   *
   * @return mixed
   */
  public function export($entity, $field_name, $value);

}
