<?php

namespace Drupal\xlsx\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * XlsxRemoteManager plugin manager.
 *
 * @package xlsx
 */
class XlsxRemoteManager extends DefaultPluginManager {

  /**
   * Constructs an XlsxRemoteManager object.
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
    parent::__construct('Plugin/xlsx/remote', $namespaces, $module_handler, 'Drupal\xlsx\Plugin\XlsxRemoteInterface', 'Drupal\xlsx\Annotation\XlsxRemote');
    $this->alterInfo('xlsx_remote_plugin_info');
    $this->setCacheBackend($cache_backend, 'xlsx_remote_plugin');
  }

}
