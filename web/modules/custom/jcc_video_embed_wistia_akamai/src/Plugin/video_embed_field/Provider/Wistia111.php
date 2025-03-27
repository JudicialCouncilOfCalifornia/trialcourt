<?php

namespace Drupal\jcc_video_embed_wistia_akamai\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * A Wistia provider plugin.
 *
 * @VideoEmbedProvider(
 *   id = "wistia",
 *   title = @Translation("Wistia")
 * )
 */
//class Wistia extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  /*public function renderEmbedCode($width, $height, $autoplay) {
    $video_id = $this->getVideoId();
    $autoplay_param = $autoplay ? 'true' : 'false';

    return [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#attributes' => [
        'src' => "https://fast.wistia.com/embed/medias/{$video_id}.jsonp",
        'async' => 'async',
      ],
    ];
  }*/

  /**
   * {@inheritdoc}
   */
  /*public function getRemoteThumbnailUrl() {
    return "https://fast.wistia.com/embed/medias/{$this->getVideoId()}/swatch";
  }

 
  public static function getIdFromInput($input) {
    preg_match('/wistia\.com\/medias\/([a-zA-Z0-9]+)/', $input, $matches);
    return $matches[1] ?? FALSE;
  }
}*/
