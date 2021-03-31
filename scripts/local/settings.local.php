<?php

/**
 * @file
 * This file provides a default starting point for settings.local.php.
 *
 * We will try to add more useful default configuration options to help speed
 * up local project setup.
 *
 * If you find common settings you use locally on many projects, please
 * feel free to add them to the project and create a PR for review.
 */

/**
 * Assertions.
 *
 * The Drupal project primarily uses runtime assertions to enforce the
 * expectations of the API by failing when incorrect calls are made by code
 * under development.
 *
 * @see http://php.net/assert
 * @see https://www.drupal.org/node/2492225
 *
 * If you are using PHP 7.0 it is strongly recommended that you set
 * zend.assertions=1 in the PHP.ini file (It cannot be changed from .htaccess
 * or runtime) on development machines and to 0 in production.
 *
 * @see https://wiki.php.net/rfc/expectations
 */
// assert_options(ASSERT_ACTIVE, TRUE);
// \Drupal\Component\Assertion\Handle::register();
/**
 * Show all error messages, with backtrace information.
 *
 * In case the error level could not be fetched from the database, as for
 * example the database connection failed, we rely only on this value.
 */
$config['system.logging']['error_level'] = 'verbose';

/**
 * Disable the render cache (this includes the page cache).
 *
 * Note: you should test with the render cache enabled, to ensure the correct
 * cacheability metadata is present. However, in the early stages of
 * development, you may want to disable it.
 *
 * This setting disables the render cache by using the Null cache back-end
 * defined by the development.services.yml file above.
 *
 * Do not use this setting until after the site is installed.
 */
$settings['cache']['bins']['render'] = 'cache.backend.null';

/**
 * Disable Dynamic Page Cache.
 *
 * Note: you should test with Dynamic Page Cache enabled, to ensure the correct
 * cacheability metadata is present (and hence the expected behavior). However,
 * in the early stages of development, you may want to disable it.
 */
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

/**
 * Allow test modules and themes to be installed.
 *
 * Drupal ignores test modules and themes by default for performance reasons.
 * During development it can be useful to install test extensions for debugging
 * purposes.
 */
// $settings['extension_discovery_scan_tests'] = TRUE;
/**
 * Enable access to rebuild.php.
 *
 * This setting can be enabled to allow Drupal's php and database cached
 * storage to be cleared via the rebuild.php page. Access to this page can also
 * be gained by generating a query string from rebuild_token_calculator.sh and
 * using these parameters in a request to rebuild.php.
 */
$settings['rebuild_access'] = TRUE;

/**
 * Skip file system permissions hardening.
 *
 * The system module will periodically check the permissions of your site's
 * site directory to ensure that it is not writable by the website user. For
 * sites that are managed with a version control system, this can cause problems
 * when files in that directory such as settings.php are updated, because the
 * user pulling in the changes won't have permissions to modify files in the
 * directory.
 */
$settings['skip_permissions_hardening'] = TRUE;

/**
 * Enable local development services.
 */
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/[site]/services.local.yml';

/**
 * Database settings: *.
 */
$databases['default']['default'] = [
  'database' => '[site]',
  'username' => 'drupal8',
  'password' => 'drupal8',
  'prefix' => '',
  'host' => 'database',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
];

/* Temp and Private dirctories. */
$config['system.file']['path']['temporary'] = '../data/tmp';
$config['system.file']['path']['private'] = '../data/private/[site]';
$settings['file_temporary_path'] = $config['system.file']['path']['temporary'];
$settings['file_private_path'] = $config['system.file']['path']['private'];

/* Performance/Cache settings */
$config['system.performance']['cache'] = ['page' => ['max_age' => 0, 'use_internal' => FALSE]];
$config['system.performance']['fast_404'] = ['enabled' => FALSE];
$config['system.performance']['css'] = ['preprocess' => FALSE, 'gzip' => FALSE];
$config['system.performance']['js'] = ['preprocess' => FALSE, 'gzip' => FALSE];
$config['system.performance']['response'] = ['gzip' => FALSE];

$config['devel.settings']['error_handlers'] = 4;
$config['devel.settings']['devel_dumper'] = 'var_dumper';
$config['system.logging']['error_level'] = 'verbose';

/* Stage File Proxy Settings */
$config['stage_file_proxy.settings']['origin'] = 'https://develop-jcc-tc.pantheonsite.io';
$config['stage_file_proxy.settings']['use_imagecache_root'] = 0;
$config['stage_file_proxy.settings']['hotlink'] = 1;

/* Environment Indicator */
$config['environment_indicator.indicator']['bg_color'] = '#045d30';
$config['environment_indicator.indicator']['fg_color'] = '#ffffff';

/* Solr */
$config['search_api.server.pantheon']['backend_config'] = [
  'connector' => 'standard',
  'connector_config' => [
    'schema' => NULL,
    'scheme' => 'http',
    'host' => 'solr',
    'port' => '8983',
    'path' => '/solr',
    'core' => 'freedom',
    'timeout' => 5,
    'index_timeout' => 5,
    'optimize_timeout' => 10,
    'commit_within' => 1000,
    'solr_version' => '',
    'http_method' => 'AUTO',
  ],
];
