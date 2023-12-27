<?php

namespace Drupal\jcc_tc_migration\Plugin\migrate\process;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateProcessPluginBase;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Skip row if another node with the same title exists.
 *
 * @MigrateProcessPlugin(
 *   id = "skip_row_if_node_exists"
 * )
 */
class SkipRowIfNodeExists extends ProcessPluginBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new SkipRowIfNodeExists object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $entity_type_manager
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Check if another node with the same title exists.
    $title = $row->getSourceProperty('title');
    $node_storage = $this->entityTypeManager->getStorage('node');
    $existing_nodes = $node_storage->loadByProperties(['title' => $title]);

    // Skip the row if another node exists.
    if (!empty($existing_nodes)) {
      return NULL;
    }

    // Return the value to proceed with the migration.
    return $value;
  }

}
