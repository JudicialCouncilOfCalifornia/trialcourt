<?php

namespace Drupal\media_boxcast\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Plugin implementation of the 'oembed' formatter.
 *
 * @internal
 *   This is an internal part of the oEmbed system and should only be used by
 *   oEmbed-related code in Drupal core.
 *
 * @FieldFormatter(
 *   id = "boxcast_content_formatter",
 *   label = @Translation("Boxcast Content Formatter"),
 *   field_types = {
 *     "boxcast_content",
 *   },
 * )
 */
class BoxcastContentFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $item) {
      $url = $item->get('url')->getCastedValue();
      $path = parse_url($url, PHP_URL_PATH);
      $args = explode('/', $path);
      $channel = array_search('channel', $args);
      $id = $args[$channel + 1];

      $elements[] = [
        '#type' => 'processed_text',
        '#format' => 'full_html',
        '#text' => "<div id='boxcast-widget-${id}' class='media-boxcast'></div>",
        '#attached' => [
          'library' => [
            'media_boxcast/field_formatter',
          ],
          'drupalSettings' => [
            'media_boxcast' => [
              $id => [
                'showTitle' => $item->show_title ? true : false,
                'showDescription' => $item->show_description ? true : false,
                'showHighlights' => $item->show_highlights ? true : false,
                'showRelated' => $item->show_related ? true : false,
                'defaultVideo' => $item->default_video,
                'market' => $item->market,
                'showCountdown' => $item->show_countdown ? true : false,
                'showDocuments' => $item->show_documents ? true : false,
                'showIndex' => $item->show_index ? true : false,
                'showDonations' => $item->show_donations ? true : false,
                'layout' => $item->layout,
              ],
            ],
          ],
        ],
      ];
    }
    return $elements;
  }

}
