<?php

/**
 * @file
 * Contains post update hooks for JCC TC.
 */

/**
 * Repairs openid_connect client config left without its entity keys.
 */
function jcc_tc_post_update_repair_openid_connect_clients() {
  // openid_connect_update_8200() only converts the old client settings into a
  // config entity when credentials are present, but it deletes the old object
  // either way. openid_connect_windows_aad_update_9202() then writes raw config
  // to that same name without going through the entity API, so sites that never
  // configured the client end up with an object holding nothing but 'settings'.
  // It loads as an entity with a NULL id, which breaks Features
  // (ConfigurationItem::__construct() gets FALSE instead of an array) and the
  // config importer (ConfigEntityStorage::importDelete() calls setSyncing() on
  // NULL). Post updates run after every hook_update_N, so this runs after 9202
  // has created the malformed object and before any later cim.
  if (!\Drupal::moduleHandler()->moduleExists('openid_connect')) {
    return 'openid_connect is not installed, nothing to repair.';
  }

  $prefix = 'openid_connect.client.';
  $active = \Drupal::service('config.storage');
  $sync = \Drupal::service('config.storage.sync');
  $config_factory = \Drupal::configFactory();
  $entity_storage = \Drupal::entityTypeManager()->getStorage('openid_connect_client');
  $plugin_manager = \Drupal::service('plugin.manager.openid_connect_client');
  $definitions = $plugin_manager->getDefinitions();
  $repaired = [];

  foreach ($active->listAll($prefix) as $name) {
    $data = $active->read($name);

    // Well formed entities carry their id, and those are left alone.
    if (!empty($data['id'])) {
      continue;
    }

    // The id is only recoverable from the config name.
    $id = substr($name, strlen($prefix));
    if ($id === '') {
      $config_factory->getEditable($name)->delete();
      $repaired[] = "$name (deleted, no id could be derived)";
      continue;
    }

    // When the site already tracks a good version of this client, restore that
    // one so the uuid keeps matching and cim sees no difference afterwards.
    $exported = $sync->read($name);
    if (!empty($exported['id'])) {
      $config_factory->getEditable($name)->setData($exported)->save(TRUE);
      $repaired[] = "$name (restored from the sync directory)";
      continue;
    }

    $plugin_id = $data['plugin'] ?? $id;
    if (!isset($definitions[$plugin_id])) {
      $repaired[] = "$name (skipped, plugin '$plugin_id' is not available)";
      continue;
    }

    // Let the plugin fill in whatever defaults the stored settings are missing.
    $settings = $plugin_manager
      ->createInstance($plugin_id, $data['settings'] ?? [])
      ->getConfiguration();

    // The raw object has to go before the entity can claim the same name.
    $config_factory->getEditable($name)->delete();

    $entity_storage->create([
      'id' => $id,
      'label' => (string) $definitions[$plugin_id]['label'],
      'plugin' => $plugin_id,
      // Without a client id the client cannot work, so it stays disabled.
      'status' => !empty($settings['client_id']),
      'settings' => $settings,
    ])->save();

    $repaired[] = "$name (rebuilt as a config entity)";
  }

  return $repaired
    ? 'Repaired openid_connect client config: ' . implode('; ', $repaired) . '.'
    : 'No malformed openid_connect client config found.';
}
