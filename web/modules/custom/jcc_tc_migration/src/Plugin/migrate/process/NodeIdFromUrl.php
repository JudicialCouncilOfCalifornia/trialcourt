<?php

namespace Drupal\jcc_tc_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a node_id_from_url process plugin.
 *
 * Resolves a URL/alias/internal path to a node ID.
 *
 * Usage:
 *
 * @code
 * process:
 *   nid:
 *     - plugin: node_id_from_url
 *       source: url
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "node_id_from_url"
 * )
 */
class NodeIdFromUrl extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    if (empty($value)) {
      return NULL;
    }

    $path = $this->normalizeToPath((string) $value);
    if ($path === '') {
      return NULL;
    }

    if (preg_match('#^/node/(\d+)$#', $path, $matches)) {
      return (int) $matches[1];
    }

    $alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');

    $alias_entities = $alias_storage->loadByProperties(['alias' => $path]);
    if (!empty($alias_entities)) {
      $alias = reset($alias_entities);
      $internal = (string) $alias->getPath();
      if (preg_match('#^/node/(\d+)$#', $internal, $matches)) {
        return (int) $matches[1];
      }
    }

    $internal_path = (string) \Drupal::service('path_alias.manager')->getPathByAlias($path);
    if (preg_match('#^/node/(\d+)$#', $internal_path, $matches)) {
      return (int) $matches[1];
    }

    return NULL;
  }

  /**
   * Converts full URL or path-like value into a normalized path.
   */
  protected function normalizeToPath(string $input): string {
    $input = trim($input);
    if ($input === '') {
      return '';
    }

    $path = preg_match('#^https?://#i', $input)
      ? (string) parse_url($input, PHP_URL_PATH)
      : $input;

    if ($path === '') {
      return '';
    }

    if ($path[0] !== '/') {
      $path = '/' . $path;
    }

    if (strlen($path) > 1) {
      $path = rtrim($path, '/');
    }

    return $path;
  }

}
