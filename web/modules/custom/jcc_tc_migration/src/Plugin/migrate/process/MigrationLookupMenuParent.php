<?php

namespace Drupal\jcc_tc_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a migration_lookup_menu_parent plugin.
 *
 * Fetches menu mlid given a source.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: migration_lookup_menu_parent
 *     source: url
 *     migration: migration
 *     lookup_id: lookup_id
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "migration_lookup_menu_parent"
 * )
 */
class MigrationLookupMenuParent extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {

    $lookup_migration_ids = (array) $this->configuration['migration'];

    foreach ($lookup_migration_ids as $lookup_migration_id) {
      if (!is_array($value)) {
        $value = [$value];
      }
      $lookup_table = 'migrate_map_' . $lookup_migration_id;

      // Query for a match.
      $database = \Drupal::database();
      $query = $database->select($lookup_table, 'lt');
      $query->fields('lt', ['destid1'])
        ->condition('sourceid1', $value);
      $result = $query->execute();
      $destination_id = $result->fetchField();

      $database = \Drupal::database();
      $query = $database->select('menu_link_content', 'lt');
      $query->fields('lt', ['uuid'])
        ->condition('id', $destination_id);
      $result = $query->execute();
      $uuid = $result->fetchField();
    }

    return $uuid ? 'menu_link_content:' . $uuid : NULL;
  }

}
