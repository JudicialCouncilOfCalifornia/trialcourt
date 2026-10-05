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
    if (empty($configuration['urls'])) {
      throw new \InvalidArgumentException('At least one URL must be provided.');
    }

    $urls = is_array($configuration['urls']) ? $configuration['urls'] : [$configuration['urls']];

    $configuration['urls'] = [];
    foreach ($urls as $url) {
      if (!is_string($url) || trim($url) === '') {
        throw new \InvalidArgumentException('Source URL is missing.');
      }

      $separator = (strpos($url, '?') === FALSE) ? '?' : '&';
      $stamped_url = $url . $separator . 'timestamp=' . time();
      // Log timestamped URL to confirm it has been correctly modified.
      \Drupal::logger('jcc_migration')->info('Timestamped URL for migration @migration: @url', [
        '@migration' => $migration->id(),
        '@url' => $stamped_url,
      ]);
      $configuration['urls'][] = $stamped_url;
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

}
