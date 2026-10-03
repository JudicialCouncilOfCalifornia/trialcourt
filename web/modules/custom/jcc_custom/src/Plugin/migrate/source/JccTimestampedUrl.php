<?php

namespace Drupal\jcc_custom\Plugin\migrate\source;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Plugin\migrate\source\Url;

/**
 * Adds timestamp param to URLs to avoid outdated cached content.
 *
 * @MigrateSource(
 *   id = "jcc_timestamped_url"
 * )
 *
 * Setup is the same as the Url source plugin.
 *
 * @code
 * plugin: jcc_timestamped_url
 * data_fetcher_plugin: http
 * data_parser_plugin: xml
 * urls: ''
 * @endcode
 */
class JccTimestampedUrl extends Url {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    $urls = is_array($configuration['urls']) ? $configuration['urls'] : [$configuration['urls']];

    $configuration['urls'] = [];
    foreach ($urls as $url) {
      $separator = (strpos($url, '?') === FALSE) ? '?' : '&';
      $configuration['urls'][] = $url . $separator . 'timestamp=' . time();
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

}
