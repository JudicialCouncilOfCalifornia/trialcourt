<?php

namespace Drupal\jcc_migrate_source_ui;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Overrides migration configuration.
 */
class JCCMigrateSourceUIOverrides implements ConfigFactoryOverrideInterface {

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    $state = \Drupal::state();
    // Set path or url overrides if available.
    $sources = $state->get('jcc_migrate_sources') ?: [];
    foreach ($sources as $migration_id => $source) {
      $overrides['migrate_plus.migration.' . $migration_id]['source'][$source['type']] = $source['uri'];
    }
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'JCCMigrateSourceUIOverrider';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
