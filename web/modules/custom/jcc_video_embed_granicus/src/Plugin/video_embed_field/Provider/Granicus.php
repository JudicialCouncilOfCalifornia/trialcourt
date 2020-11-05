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
    $theme = 'jcc-video--granicus';
    $classes = $theme;
    $tag = $this->getTag($this->getInput());
    if (isset($tag) && !empty($tag)) {
      $classes = $theme . ' ' . $tag;
    }
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => $classes,
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
        'src' => sprintf('//jcc.granicus.com/player/%s/%s?redirect=true%s%s&autostart=%d&embed=1', $this->getType($this->getInput()), $this->getVideoId(), $this->getStartTime($this->getInput()), $this->getStopTime($this->getInput()), $autoplay),
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
   * Determine if Granicus stream or recording.
   *
   * {@inheritdoc}
   */
  public static function getType($input) {
    preg_match('/^https?:\/\/jcc.granicus.com\/(player\/(?<type>[a-zA-Z0-9]*))/', $input, $matches);
    return isset($matches['type']) ? $matches['type'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/^https?:\/\/jcc.granicus.com\/(player\/([a-zA-Z0-9]*))\/(?<id>[0-9A-Za-z_-]*)/', $input, $matches);
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

  /**
   * If Granicus recording, optional auto start time marker.
   *
   * {@inheritdoc}
   */
  public static function getStartTime($input) {
    preg_match('/[&|&amp;\?]entrytime=(?<entrytime>\d+)?/', $input, $matches);
    return isset($matches['entrytime']) ? '&entrytime=' . $matches['entrytime'] : FALSE;
  }

  /**
   * If Granicus recording, optional auto stop time marker.
   *
   * {@inheritdoc}
   */
  public static function getStopTime($input) {
    preg_match('/[&|&amp;\?]stoptime=(?<stoptime>\d+)?/', $input, $matches);
    return isset($matches['stoptime']) ? '&stoptime=' . $matches['stoptime'] : FALSE;
  }

  /**
   * If Granicus recording, optional tag.
   *
   * {@inheritdoc}
   */
  public static function getTag($input) {
    preg_match('/[&|&amp;\?]tag=(?<tag>\D+)?/', $input, $matches);
    return isset($matches['tag']) ? $matches['tag'] : FALSE;
  }

}
