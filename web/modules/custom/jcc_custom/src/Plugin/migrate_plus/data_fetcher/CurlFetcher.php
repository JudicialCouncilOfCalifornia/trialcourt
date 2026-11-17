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

    $command = "curl --http1.1 --fail --location --silent --show-error --connect-timeout 15 --max-time 60 -A " . escapeshellarg($userAgent) . " " . escapeshellarg($url);
    $output = [];
    $exit_code = 0;
    exec($command . ' 2>/dev/null', $output, $exit_code);
    if ($exit_code !== 0) {
      throw new \RuntimeException(sprintf('Migration source download failed (curl exit code %d).', $exit_code));
    }
    $data = implode("\n", $output);
    if (trim($data) === '') {
      throw new \RuntimeException('Migration source returned an empty response.');
    }
    if (($this->configuration['data_parser_plugin'] ?? NULL) === 'json') {
      try {
        json_decode($data, TRUE, 512, JSON_THROW_ON_ERROR);
      }
      catch (\JsonException $e) {
        throw new \RuntimeException('Migration source returned invalid JSON.', 0, $e);
      }
    }
    return $data;
  }

}
