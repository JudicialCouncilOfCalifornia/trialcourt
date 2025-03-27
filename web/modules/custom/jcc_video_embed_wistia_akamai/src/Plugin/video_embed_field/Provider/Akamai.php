<?php

namespace Drupal\jcc_video_embed\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * A Akamai provider plugin.
 *
 * @VideoEmbedProvider(
 *   id = "akamai",
 *   title = @Translation("Akamai")
 * )
 */
class Akamai extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderThumbnail($image_style, $link_url) {
    // Akamai does not provide a direct thumbnail URL, using a placeholder.
    return [
      '#type' => 'html_tag',
      '#tag' => 'img',
      '#attributes' => [
        'src' => '/modules/custom/jcc_video_embed/img/default-thumbnail.png', // Provide a default image
        'alt' => $this->getInput(),
        'width' => 300,
        'height' => 200,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    return [
      '#type' => 'html_tag',
      '#tag' => 'video',
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'controls' => 'controls',
        'autoplay' => $autoplay ? 'autoplay' : NULL,
      ],
      '#attached' => [
        'html_head' => [
          [
            [
              '#type' => 'html_tag',
              '#tag' => 'source',
              '#attributes' => [
                'src' => $this->getInput(),
                'type' => 'video/mp4',
              ],
            ],
            'akamai_video_source',
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    // Akamai does not provide direct thumbnails.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
 /**
 * {@inheritdoc}
 */
public static function getIdFromInput($input) {
  // Return the full URL since Akamai uses direct MP4 links.
  return filter_var($input, FILTER_VALIDATE_URL) ? $input : FALSE;
}

}
