<?php

namespace Drupal\jcc_ckeditor\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * A filter that converts | in tokens into :.
 *
 * @Filter(
 *   id = "filter_token_links",
 *   title = @Translation("Token Attributes Filter"),
 *   description = @Translation("Use tokens in html attributes e.g. [site|name] instead of [site:name]."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class FilterTokenAttributes extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $processedText = preg_replace_callback(
      '/(desktop--\s|tablet--\s|mobile-lg--\s|tablet-lg--\s)/',
      function ($matches) {
        return str_replace('-- ', ':', $matches[0]);
      },
      $text
    );
    return new FilterProcessResult($processedText ?? $text);
  }

}
