<?php

namespace Drupal\xlsx\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * XlsxExportManager plugin manager.
 *
 * @package xlsx
 */
class XlsxExportManager extends DefaultPluginManager {

  /**
   * Constructs an XlsxExportManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/xlsx/export', $namespaces, $module_handler, 'Drupal\xlsx\Plugin\XlsxExportInterface', 'Drupal\xlsx\Annotation\XlsxExport');
    $this->alterInfo('xlsx_export_plugin_info');
    $this->setCacheBackend($cache_backend, 'xlsx_export_plugin');
  }

}
