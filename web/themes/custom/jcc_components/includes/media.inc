<?php

/**
 * @file
 * Preprocess and functions for paragraphs.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_media(&$variables) {
  $media = $variables['media'];

  if ($media->bundle() == 'remote_video') {
    // Extract the video ID from the url.
    $url = $media->field_media_oembed_video->value;
    $parsed = parse_url($url);
    $query = explode('&', $parsed['query']);
    foreach ($query as $param) {
      if (strpos($param, 'v=') === 0) {
        $variables['video_id'] = str_replace('v=', '', $param);
      }
    }
    if (empty($variables['video_id'])) {
      $args = explode('/', $parsed['path']);
      $variables['video_id'] = $args[1];
    }
  }
}
