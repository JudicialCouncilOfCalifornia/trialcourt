<?php

namespace Drupal\jcc_tc_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a path_from_url plugin.
 *
 * Returns the path portion of a url.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: path_from_url
 *     source: url
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "path_from_url"
 * )
 */
class PathFromUrl extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {

    $path = parse_url($value, PHP_URL_PATH);

    // Remove trailing slash.
    // @see https://www.drupal.org/project/redirect/issues/2932615
    $path = rtrim($path, '/');

    return $path;
  }

}
