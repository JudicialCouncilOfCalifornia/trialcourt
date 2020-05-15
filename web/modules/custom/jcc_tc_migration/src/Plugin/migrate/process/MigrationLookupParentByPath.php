<?php

namespace Drupal\jcc_tc_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a migration_lookup_parent_by_path plugin.
 *
 * Fetches book/parent nid from migration table, given a topic code.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: migration_lookup_parent_by_path
 *     source: url
 *     path_part: path_parts
 *     parent_path_prefix: string
 *     parent_path_suffix: string
 *     migration: migration
 *     lookup_id: lookup_id
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "migration_lookup_parent_by_path"
 * )
 *
 */
class MigrationLookupParentByPath extends ProcessPluginBase {

  /**
  * {@inheritdoc}
  */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {

    $lookup_migration_ids = (array) $this->configuration['migration'];
    $parent_path_prefix = $this->configuration['parent_path_prefix'];
    $parent_path_suffix = $this->configuration['parent_path_suffix'];
    $path_part = !is_array($this->configuration['path_part']) ? [$this->configuration['path_part']] : $this->configuration['path_part'];
    $lookup_id = $this->configuration['lookup_id'];

    $self = FALSE;
    $destination_ids = NULL;
    $lookup_value = '';

    foreach ($lookup_migration_ids as $lookup_migration_id) {
      if (!is_array($value)) {
        $value = [$value];
      }

      $lookup_table = 'migrate_map_' . $lookup_migration_id;
      // Set lookup field by id index.
      $i = 0;
      $lookup_field = '';
      foreach($row->getSourceIdValues() as $k => $v) {
        $i ++;
        $lookup_field = $k == $lookup_id ? 'sourceid' . $i : $lookup_field;
      }
      // Set the look up path from row url parts and prefix/suffix.
      $path = parse_url(reset($value), PHP_URL_PATH);
      $parts = explode('/', $path);
      // Assemble the parts of the path we want for the lookup.
      foreach($path_part as $i) {
        $lookup_value .= '/' . $parts[$i];
      }
      // Add static prefix and suffix.
      $lookup_value = $parent_path_prefix ? $parent_path_prefix . $lookup_value : $lookup_value;
      $lookup_value = $parent_path_suffix ? $lookup_value . '/' . $parent_path_suffix : $lookup_value;
      // Query for a match.
      $database = \Drupal::database();
      $query = $database->select($lookup_table, 'lt');
      $query->fields('lt', ['destid1'])
        ->condition($lookup_field, '%' . $lookup_value . '%', 'LIKE');
      $result = $query->execute();
      $destination_id = $result->fetchField();

    }
    return $destination_id;
  }

}
