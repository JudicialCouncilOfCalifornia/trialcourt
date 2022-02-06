<?php

namespace Drupal\xlsx\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an XLSX entity.
 */
interface XlsxEntityInterface extends ConfigEntityInterface {

  /** 
   * Set XLSX mapping.
   */
  public function setMapping($mapping);

  /** 
   * Get XLSX mapping.
   */
  public function getMapping();

  /** 
   * Get mapping source plugin.
   */
  public function getSourcePlugin();

  /** 
   * Get mapping export plugins.
   */
  public function getExportPlugins();

  /** 
   * Check if mapping for export only.
   */
  public function isExportOnly();

  /** 
   * Set last import timestamp.
   */
  public function setLastImport();

  /** 
   * Get last import timestamp.
   */
  public function getLastImport();

  /** 
   * Set last export timestamp.
   */
  public function setLastExport();

  /** 
   * Get last export timestamp.
   */
  public function getLastExport();

}
