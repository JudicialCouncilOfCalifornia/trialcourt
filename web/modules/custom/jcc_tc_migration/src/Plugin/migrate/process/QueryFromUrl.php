<?php

namespace Drupal\jcc_tc_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a query_from_url plugin.
 *
 * Returns the query portion of a url as an array.
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
 *   id = "query_from_url"
 * )
 */
class QueryFromUrl extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    parse_str(parse_url($value, PHP_URL_QUERY), $query);

    return $query;
  }

}
