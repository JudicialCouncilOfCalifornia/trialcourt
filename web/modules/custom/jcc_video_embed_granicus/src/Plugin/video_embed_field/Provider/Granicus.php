<?php

namespace Drupal\jcc_video_embed_granicus\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * A Granicus provider plugin.
 *
 * @VideoEmbedProvider(
 *   id = "granicus",
 *   title = @Translation("Granicus")
 * )
 */
class Granicus extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderThumbnail($image_style, $link_url) {
    // Insert placeholder for thumbnail not found.
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => 'jcc-placeholder-video',
      ],
      [
        '#type' => 'html_tag',
        '#tag' => 'i',
        '#attributes' => [
          'class' => 'fa fa-play',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $embed_code = [
      '#type' => 'html_tag',
      '#provider' => 'granicus',
      '#tag' => 'embed',
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
        'src' => sprintf('//jcc.granicus.com/player/%s&redirect=true&autostart=%d&embed=1', $this->getVideoId(), $autoplay),
      ],
    ];
    return $embed_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    // Remote thumbnail not supported at this time.
    return NULL;
  }

  /**
   * Handling all parameters since wysiwyg embed only sees id currently.
   *
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/^https?:\/\/jcc.granicus.com\/(player\/(?<type>[a-zA-Z0-9]*))/', $input, $matchType);
    $videoType = isset($matchType['type']) ? $matchType['type'] : FALSE;
    preg_match('/\/(player\/([a-zA-Z0-9]*))\/(?<id>[0-9A-Za-z_-]*)/', $input, $matchId);
    $videoId = isset($matchId['id']) ? $matchId['id'] : FALSE;
    if ($videoType == 'clip') {
      preg_match('/[&|&amp;\?]entrytime=((?<entrytime>\d+))?/', $input, $matchStart);
      $videoStart = isset($matchStart['entrytime']) ? $matchStart['entrytime'] : FALSE;
      preg_match('/[&|&amp;\?]stoptime=((?<stoptime>\d+))?/', $input, $matchStop);
      $videoStop = isset($matchStop['stoptime']) ? $matchStop['stoptime'] : FALSE;
    }

    switch ($videoType) {
      case 'clip':
        $embedParams = $videoType . '/' . $videoId . '?' . '&entrytime=' . $videoStart . '&stoptime=' . $videoStop;
        break;
        
      default:
        $embedParams = $videoType . '/' . $videoId . '?';
    }

    return $embedParams;
  }

  /**
   * Get the starting point within the recording.
   *
   * @return int|false
   *   A timeline parameter to pass to the embed src or FALSE if none is found.
   */
  protected function getEntryTime() {
    preg_match('/[&\?]entrytime=((?<entrytime>\d+))?/', $this->input, $matches);
    return isset($matches['entrytime']) ? $matches['entrytime'] : FALSE;
  }

  /**
   * Get the stopping point within the recording.
   *
   * @return int|false
   *   A timeline parameter to pass to the embed src or FALSE if none is found.
   */
  protected function getStopTime() {
    preg_match('/[&\?]stoptime=((?<stoptime>\d+))?/', $this->input, $matches);
    return isset($matches['stoptime']) ? $matches['stoptime'] : FALSE;
  }

}
