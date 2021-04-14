<?php

namespace Drupal\jcc_components_variants;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Custom Twig features and filters.
 */
class CustomTwig extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('json_decode', [$this, 'jsonDecode']),
    ];
  }

  /**
   * Decodes a JSON string into an object or array.
   *
   * @param string $value
   *   The JSON string to decode.
   * @param bool $assoc
   *   If TRUE, will convert JSON to an associative array instead of an object.
   *
   * @return array|object
   *   The object or array equivalent of the JSON string.
   */
  public static function jsonDecode($value, $assoc = FALSE) {
    if (is_string($value)) {
      return json_decode($value, $assoc);
    }

    return [];
  }

}
