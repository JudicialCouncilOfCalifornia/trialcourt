<?php

namespace Drupal\jcc_elevated_custom\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension jcc_extract_body that gets the value of <body>.
 */
class JccElevatedCustomTwigExtractBody extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('jcc_extract_body', [$this, 'jccExtractBodyFilter']),
    ];
  }

  /**
   * The body extract filter method.
   *
   * @param string $text
   *   The input text to filter.
   *
   * @return string
   *   The filtered text.
   */
  public function jccExtractBodyFilter($text) {
    if (preg_match('/<body[^>]*>/i', $text)) {
      // Extract content within <body> tags with DomDocument.
      $dom = new \DOMDocument();
      // Suppress errors due to malformed HTML.
      @$dom->loadHTML($text);
      $body = $dom->getElementsByTagName('body')->item(0);
      if ($body) {
        $text = strip_tags($dom->saveHTML($body));
        $pattern = '/\b(?:January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{1,2},\s+\d{4}\b/';
        $text = preg_replace($pattern, '', $text);
        $text = str_replace(
          [
            "If you're having trouble viewing this email, you may see it online.",
          ],
          '', $text);
        $text = preg_replace('/\s+/', ' ', trim($text));
      }
    }
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'jcc_elevated_custom.twig_extract_body';
  }

}
