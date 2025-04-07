<?php

namespace Drupal\jcc_video_embed_wistia_akamai\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * A Akamai Audio provider plugin.
 *
 * @VideoEmbedProvider(
 *   id = "akamai_audio",
 *   title = @Translation("Akamai Audio")
 * )
 */
class AkamaiAudio extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    return [
      '#type' => 'html_tag',
      '#tag' => 'audio',
      '#attributes' => [
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
                'type' => 'audio/mpeg',
              ],
            ],
            'akamai_audio_source',
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    return filter_var($input, FILTER_VALIDATE_URL) ? $input : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return NULL; // No thumbnail for audio.
  }
}
