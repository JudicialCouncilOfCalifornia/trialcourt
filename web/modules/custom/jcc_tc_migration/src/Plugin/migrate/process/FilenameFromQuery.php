<?php

namespace Drupal\jcc_tc_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;

/**
 * Get filename from URL.
 *
 * Special rules for extracting filenames from urls.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: filename_from_query
 *     url: uri
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "filename_from_query"
 * )
 */
class FilenameFromQuery extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    $url = $row->get($this->configuration['url'] ?? NULL);
    if (empty(pathinfo($value, PATHINFO_EXTENSION)) && $url) {
      // URLs of type: "https://www.abc.com/ShowPDF/?pdf=filename.pdf"
      if (strpos($url, 'ShowPDF/?pdf=') !== FALSE) {
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $value = $query['pdf'] ?? $value;
      }
      // URLs oftype:  "https://www.abc.com/download/?packet=filename"
      elseif (strpos($url, '/download/?packet=') !== FALSE) {
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $filename = $query['packet'] ?? $value;
        try {
          $headers = get_headers($url, 1);
          $contentType = $headers['Content-Type'] ?? NULL;
          $contentType = is_array($contentType) ? reset($contentType) : $contentType;
        }
        catch (RequestException $exception) {
          return $filename;
        }

        if ($contentType) {
          $extension_guesser = ExtensionGuesser::getInstance();
          $extension = $extension_guesser->guess($contentType);
          $extension = empty($extension) && ($contentType == 'application/x-zip-compressed;charset=UTF-8') ? 'zip' : NULL;
          $value = $extension ? $filename . '.' . $extension : $filename;
        }
      }
    }

    return $value;
  }

}
