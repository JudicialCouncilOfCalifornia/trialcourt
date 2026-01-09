<?php

namespace Drupal\jcc_custom\Plugin\migrate_plus\data_fetcher;

use Drupal\migrate_plus\Plugin\migrate_plus\data_fetcher\Http;

/**
 * Provides a data fetcher that uses system-level Curl.
 *
 * @DataFetcher(
 * id = "curl_fetcher",
 * title = @Translation("Curl Fetcher")
 * )
 */
class CurlFetcher extends Http {

  /**
   * {@inheritdoc}
   */
  public function getResponseContent(string $url): string {
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    $command = "curl --http1.1 -L -s -A " . escapeshellarg($userAgent) . " " . escapeshellarg($url);

    $data = (string) shell_exec($command);

    return $data;
  }

}
