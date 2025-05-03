<?php

namespace Drupal\jcc_video_embed_akamai\Plugin\video_embed_field\Provider;

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
        'style' => 'width:100%;',
      ],
      '#markup' => '<source src="' . $this->getInput() . '" type="audio/mpeg">',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    // Only allow URLs ending in .mp3 to avoid conflicting with video.
    if (filter_var($input, FILTER_VALIDATE_URL) && preg_match('/\.mp3$/i', $input)) {
      return $input;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return NULL;
  }

}
