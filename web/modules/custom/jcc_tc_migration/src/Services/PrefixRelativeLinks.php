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
      // The url for the link to determine file name.
      $href = $item->getAttribute('href');
      $scheme = parse_url($href, PHP_URL_SCHEME);
      // Add leading slash to original link if needed.
      if (!$scheme) {
        $path = parse_url($href, PHP_URL_PATH);
        if (strpos($path, '/') !== 0) {
          $new_path = "/$path";
          // Replace the original href string in the full markup in $value.
          $value = str_replace($path, $new_path, $value);
        }
      }
    }

    return $value;
  }

}
