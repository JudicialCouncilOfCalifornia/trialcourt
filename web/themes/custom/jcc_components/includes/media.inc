<?php

/**
 * @file
 * Preprocess and functions for paragraphs.
 */

use Drupal\Component\Utility\Html;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_media(&$variables) {
  $media = $variables['media'];

  // The following code replaces image alignment feature for WYSIWYG content.
  // Image alignment is got from 'attributes'.
  if ($media->bundle() == 'image') {
    $media = $variables['media'];
    $view_mode = $variables['view_mode'];
    if (!empty($variables['attributes']['class']) && is_array($variables['attributes']['class'])) {
      $img_classes = [
        'image',
        Html::getClass('image__' . $view_mode),
        Html::getClass($variables['attributes']['class'][0]) // alignment class
      ];
    } else {
      $img_classes = [
        'image',
        Html::getClass('image__' . $view_mode)
      ];
    }

    $variables['content']['field_media_image'][0]['#item_attributes']['class'][] = implode(' ', $img_classes);

  }

  if ($media->bundle() == 'remote_video') {
    $view_mode = $variables['view_mode'];
    $view_mode_alignments = [
      'container_25_percent' => 'left',
      'container_50_percent' => 'left',
      'container_75_percent' => 'center',
      'container_100_percent' => 'none',
    ];

    // Add additional CSS Classes directly to Remote Video Element.
    $remote_video_classes = [
      Html::getClass('video__' . $view_mode),
    ];

    $variables['video_classes'] = implode(' ', $remote_video_classes);

    // Get the current path for the media embed.
    $current_path = \Drupal::service('path.current')->getPath();
    $variables['path'] = $current_path;
    // Extract the video ID from the url.
    $url = $media->field_media_oembed_video->value;
    $parsed = parse_url($url) ?? [];
    $query = isset($parsed['query']) ? explode('&', $parsed['query']) : [];
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
