<?php

namespace Drupal\jcc_courtyard_icons\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Formatter that shows and icon from Courtyard.
 *
 * @FieldFormatter(
 *   id = "courtyard_icons_formatter",
 *   label = @Translation("Courtyard Icons"),
 *   field_types = {
 *     "courtyard_icons"
 *   }
 * )
 */
class CourtyardIconsFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $name = '';

    foreach ($items as $delta => $item) {
      foreach ($item->toArray() as $name) {
        $elements[$delta] = [
          '#type' => 'processed_text',
          '#format' => 'full_html',
          '#text' => "<svg role='img' aria-label='$name'><use xlink:href='#$name'></use></svg>",
          'name' => $name,
        ];
      }
    }

    return $elements;
  }

}
