<?php

namespace Drupal\jcc_video_embed_granicus_recordings\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * A Granicus recordings provider plugin.
 *
 * @VideoEmbedProvider(
 *   id = "granicus_recordings",
 *   title = @Translation("Granicus Recordings")
 * )
 */
class GranicusRecordings extends ProviderPluginBase {

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
    if ($this->getEntryTime()) {
      $entrytime = '&entrytime=' . $this->getEntryTime();
    }
    if ($this->getStopTime()) {
      $stoptime = '&stoptime=' . $this->getStopTime();
    }
    $embed_code = [
      '#type' => 'html_tag',
      '#provider' => 'granicus_recordings',
      '#tag' => 'embed',
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
        'src' => sprintf('//jcc.granicus.com/player/clip/%s?&redirect=true' . $entrytime . $stoptime . '&autostart=%d&embed=1', $this->getVideoId(), $autoplay),
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
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/^https?:\/\/jcc.granicus.com\/(player\/clip\/)(?<id>[0-9A-Za-z_-]*)/', $input, $matches);
    return isset($matches['id']) ? $matches['id'] : FALSE;
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
