<?php

namespace Drupal\jcc_migrate_source_ui;

use Drupal\migrate_tools\MigrateBatchExecutable as BaseMigrateBatchExecutable;

/**
 * Uses the current Migrate Tools batch implementation for source UI imports.
 */
class MigrateBatchExecutable extends BaseMigrateBatchExecutable {

  /**
   * {@inheritdoc}
   */
  public static function batchProcessImport(string $migration_id, array $options, &$context): void {
    // Migrate Tools counts the source before dispatching PRE_IMPORT. Activate
    // cache busting before that first download, even for an initialized source.
    $modifier = \Drupal::service('jcc_custom.migration_source_url_modifier_subscriber');
    $migration = \Drupal::service('plugin.manager.migration')->createInstance($migration_id, $options['configuration'] ?? []);
    $modifier->prepare($migration);
    try {
      parent::batchProcessImport($migration_id, $options, $context);
    }
    finally {
      $modifier->reset();
    }
  }

}
