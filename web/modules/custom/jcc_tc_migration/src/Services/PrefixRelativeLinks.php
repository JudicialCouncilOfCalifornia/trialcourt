<?php

namespace Drupal\jcc_tc_migration\Services;

/**
 * Class PrefixRelativeLinks.
 *
 * Add leading slash to relative paths when needed.
 */
class PrefixRelativeLinks {

  /**
   * Add a lead / to relative links in markup when required.
   *
   * @param string $value
   *   The html in which to replace links.
   *
   * @return string
   *   The updated string.
   */
  public function replace($value) {
    $dom = new \DomDocument();
    $dom->loadHTML($value);

    foreach ($dom->getElementsByTagName('a') as $item) {
      // The original link string that will be replaced.
      $original = $dom->saveHTML($item);
      // The original link text for reconstructing link.
      $text = $item->nodeValue;
      // The url for the link to determine file name.
      $href = $item->getAttribute('href');
      $path = parse_url($href, PHP_URL_PATH);
      // Add leading slash to original link if needed.
      if (stripos($path, 'http') !== 0) {
        if (strpos($path, '/') !== 0) {
          $new_path = "/$path";
          // Create full replacement link.
          $replace = str_replace($path, $new_path, $original);
          // Replace the original link string in the full markup in $value.
          $value = str_replace($original, $replace, $value);
        }
      }
    }

    return $value;
  }

}
