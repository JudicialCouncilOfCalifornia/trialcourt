<?php

// @codingStandardsIgnoreFile

/**
 * Node migration type.
 *
 * This is used to force the migration system to use the classic node migrations
 * instead of the default complete node migrations. The migration system will
 * use the classic node migration only if there are existing migrate_map tables
 * for the classic node migrations and they contain data. These tables may not
 * exist if you are developing custom migrations and do not want to use the
 * complete node migrations. Set this to TRUE to force the use of the classic
 * node migrations.
 */
$settings['migrate_node_migrate_type_classic'] = TRUE;

/**
 * Salt for one-time login links, cancel links, form tokens, etc.
 *
 * This variable will be set to a random value by the installer. All one-time
 * login links will be invalidated if the value is changed. Note that if your
 * site is deployed on a cluster of web servers, you must ensure that this
 * variable has the same value on each server.
 *
 * For enhanced security, you may set this variable to the contents of a file
 * outside your document root; you should also ensure that this file is not
 * stored with backups of your database.
 *
 * Example:
 * @code
 *   $settings['hash_salt'] = file_get_contents('/home/example/salt.txt');
 * @endcode
 */
$settings['hash_salt'] = 'ycHlN4UTqt862sQnTRKwBaZ2hShhEYl0zkOZQ5fFc2ZBdYU8txhd3BsJ8h1tHXyU7Hm0P-nZ5g';

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

/**
 * The default list of directories that will be ignored by Drupal's file API.
 *
 * By default ignore node_modules and bower_components folders to avoid issues
 * with common frontend tools and recursive scanning of directories looking for
 * extensions.
 *
 * @see file_scan_directory()
 * @see \Drupal\Core\Extension\ExtensionDiscovery::scanDirectory()
 */
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

/**
 * The default number of entities to update in a batch process.
 *
 * This is used by update and post-update functions that need to go through and
 * change all the entities on a site, so it is useful to increase this number
 * if your hosting configuration (i.e. RAM allocation, CPU speed) allows for a
 * larger number of entities to be processed in a single batch run.
 */
$settings['entity_update_batch_size'] = 50;

/**
 * Entity update backup.
 *
 * This is used to inform the entity storage handler that the backup tables as
 * well as the original entity type and field storage definitions should be
 * retained after a successful entity update process.
 */
$settings['entity_update_backup'] = TRUE;

if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  $pantheon_settings = $app_root . '/' . $site_path .  '/settings.pantheon.php';
  if (file_exists($pantheon_settings)) {
    include $pantheon_settings;
  }
}

/**
 * Location of the site configuration files.
 *
 * The $config_directories array specifies the location of file system
 * directories used for configuration data. On install, the "sync" directory is
 * created. This is used for configuration imports. The "active" directory is
 * not created by default since the default storage for active configuration is
 * the database rather than the file system. (This can be changed. See "Active
 * configuration settings" below).
 *
 * The default location for the "sync" directory is inside a randomly-named
 * directory in the public files path. The setting below allows you to override
 * the "sync" location.
 *
 * If you use files for the "active" configuration, you can tell the
 * Configuration system where this directory is located by adding an entry with
 * array key CONFIG_ACTIVE_DIRECTORY.
 *
 * Example:
 * @code
 *   $config_directories = [
 *     CONFIG_SYNC_DIRECTORY => '/directory/outside/webroot',
 *   ];
 * @endcode
 */

$settings['config_sync_directory'] = '../config/config-inyo';
$config['config_split.config_split.local']['folder'] = '../config/config-inyo-local';
$settings['file_public_path']  = 'sites/default/files/inyo/default';

// Require HTTPS across all Pantheon environments.
if (isset($_SERVER['PANTHEON_ENVIRONMENT']) && ($_SERVER['HTTPS'] === 'OFF') && (php_sapi_name() != "cli")) {
  if (!isset($_SERVER['HTTP_USER_AGENT_HTTPS']) || (isset($_SERVER['HTTP_USER_AGENT_HTTPS']) && $_SERVER['HTTP_USER_AGENT_HTTPS'] != 'ON')) {

    header('HTTP/1.0 301 Moved Permanently');
    header('Location: https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

    // Name transaction "redirect" in New Relic for improved reporting (optional).
    if (extension_loaded('newrelic')) {
      newrelic_name_transaction("redirect");
    }

    exit();
  }
}

/**
 * Set environment-specific configuration.
 */
if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {

  if (file_exists($app_root . '/' . $site_path . '/settings.pantheon.php')) {
    include $app_root . '/' . $site_path . '/settings.pantheon.php';
  }

  // Live.
  if ($_ENV['PANTHEON_ENVIRONMENT'] == 'live') {
    $config['config_split.config_split.prod']['status'] = TRUE;
  }

  // Develop.
  else if ($_ENV['PANTHEON_ENVIRONMENT'] == 'develop') {

    // We need password reset emails using SendGrid from `develop`,
    // so for now, `develop` multidev should use the production configuration.
    // After launch, this will likely be changed.
    $config['config_split.config_split.prod']['status'] = TRUE;
    $config['environment_indicator.indicator']['bg_color'] = '#5b0ca3';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
  }

  // All other multidevs.
  else {
    $config['config_split.config_split.stage']['status'] = TRUE;
    $config['environment_indicator.indicator']['bg_color'] = '#0a07a9';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
  }


  if (in_array($_ENV['PANTHEON_ENVIRONMENT'], array('live', 'dev'))) {
    $config['search_api.server.solr']['backend_config']['connector_config']['core'] = 'jcc-inyo-live';
  }
  else if ($_ENV['PANTHEON_ENVIRONMENT'] == 'stage') {
    $config['search_api.server.solr']['backend_config']['connector_config']['core'] = 'jcc-inyo-stage';
  }
  else {
    $config['search_api.server.solr']['backend_config']['connector_config']['core'] = 'jcc-inyo-develop';
  }
}

// Assumes local.
else {
  $config['config_split.config_split.local']['status'] = TRUE;
  $config['config_split.config_split.stage']['status'] = TRUE;
  $config['search_api.server.solr']['backend_config']['connector_config']['core'] = 'jcc-inyo-sandbox-1';
}

/**
 * Load local development override configuration, if available.
 *
 * Use settings.local.php to override variables on secondary (staging,
 * development, etc) installations of this site. Typically used to disable
 * caching, JavaScript/CSS compression, re-routing of outgoing emails, and
 * other things that should not happen on development and testing sites.
 *
 * Keep this code block at the end of this file to take full effect.
 */

if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}

// Configure Redis
if (defined(
    'PANTHEON_ENVIRONMENT'
  ) && !\Drupal\Core\Installer\InstallerKernel::installationAttempted(
  ) && extension_loaded('redis')) {
  // Set Redis as the default backend for any cache bin not otherwise specified.
  $settings['cache']['default'] = 'cache.backend.redis';

  //phpredis is built into the Pantheon application container.
  $settings['redis.connection']['interface'] = 'PhpRedis';

  // These are dynamic variables handled by Pantheon.
  $settings['redis.connection']['host'] = $_ENV['CACHE_HOST'];
  $settings['redis.connection']['port'] = $_ENV['CACHE_PORT'];
  $settings['redis.connection']['password'] = $_ENV['CACHE_PASSWORD'];

  $settings['redis_compress_length'] = 100;
  $settings['redis_compress_level'] = 1;

  $settings['cache_prefix']['default'] = 'pantheon-redis';

  $settings['cache']['bins']['form'] = 'cache.backend.database'; // Use the database for forms

  // Apply changes to the container configuration to make better use of Redis.
  // This includes using Redis for the lock and flood control systems, as well
  // as the cache tag checksum. Alternatively, copy the contents of that file
  // to your project-specific services.yml file, modify as appropriate, and
  // remove this line.
  $settings['container_yamls'][] = 'modules/contrib/redis/redis.services.yml';

  // Allow the services to work before the Redis module itself is enabled.
  $settings['container_yamls'][] = 'modules/contrib/redis/redis.services.yml';

  // Manually add the classloader path, this is required for the container
  // cache bin definition below.
  $class_loader->addPsr4('Drupal\\redis\\', 'modules/contrib/redis/src');

  /**
   * Default TTL for Redis is 1 year.
   *
   * Change cache expiration (TTL) for data,default bin - 12 hours.
   * Change cache expiration (TTL) for entity bin - 48 hours.
   *
   * @see \Drupal\redis\Cache\CacheBase::LIFETIME_PERM_DEFAULT
   */

  $settings['redis.settings']['perm_ttl'] = 2630000; // 30 days
  $settings['redis.settings']['perm_ttl_config'] = 43200;
  $settings['redis.settings']['perm_ttl_data'] = 43200;
  $settings['redis.settings']['perm_ttl_default'] = 43200;
  $settings['redis.settings']['perm_ttl_entity'] = 172800;

  // Use redis for container cache.
  // The container cache is used to load the container definition itself, and
  // thus any configuration stored in the container itself is not available
  // yet. These lines force the container cache to use Redis rather than the
  // default SQL cache.
  $settings['bootstrap_container_definition'] = [
    'parameters' => [],
    'services' => [
      'redis.factory' => [
        'class' => 'Drupal\redis\ClientFactory',
      ],
      'cache.backend.redis' => [
        'class' => 'Drupal\redis\Cache\CacheBackendFactory',
        'arguments' => [
          '@redis.factory',
          '@cache_tags_provider.container',
          '@serialization.phpserialize',
        ],
      ],
      'cache.container' => [
        'class' => '\Drupal\redis\Cache\PhpRedis',
        'factory' => ['@cache.backend.redis', 'get'],
        'arguments' => ['container'],
      ],
      'cache_tags_provider.container' => [
        'class' => 'Drupal\redis\Cache\RedisCacheTagsChecksum',
        'arguments' => ['@redis.factory'],
      ],
      'serialization.phpserialize' => [
        'class' => 'Drupal\Component\Serialization\PhpSerialize',
      ],
    ],
  ];
}
