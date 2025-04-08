<?php

namespace Drupal\jcc_video_embed_akamai\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * A Akamai provider plugin.
 *
 * @VideoEmbedProvider(
 *   id = "akamai_video",
 *   title = @Translation("Akamai")
 * )
 */
class AkamaiVideo extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderThumbnail($image_style, $link_url) {  
    return [
      '#type' => 'html_tag',
      '#tag' => 'img',
      '#attributes' => [
        'src' => '/modules/custom/jcc_video_embed/img/default-thumbnail.png', 
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
      '#children' => '<source src="' . $this->getInput() . '" type="video/mp4">',
    ];
  }
  

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {   
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
