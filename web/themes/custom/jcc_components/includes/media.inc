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
  // Image alignment is determined by the view mode rather that editor choice.
  // If rolled back, re-enable data-align config in the text editor:
  // @see /admin/config/content/formats/manage/aside?destination=/admin/config/content/formats
  // @see /admin/config/content/formats/manage/body?destination=/admin/config/content/formats
  if ($media->bundle() == 'image') {
    $view_mode = $variables['view_mode'];
    $view_mode_alignments = [
      'container_25_percent' => 'left',
      'container_50_percent' => 'left',
      'container_75_percent' => 'center',
      'container_100_percent' => 'none',
    ];

    // Update Data Align from View Mode.
    $variables['attributes']['data-align'] = !empty($view_mode_alignments[$view_mode]) ? $view_mode_alignments[$view_mode] : 'none';

    // Add additional CSS Classes directly to Image Tag.
    $img_classes = [
      'image',
      Html::getClass('image__' . $view_mode),
      Html::getClass('align-' . $variables['attributes']['data-align']),
    ];

    $variables['content']['field_media_image'][0]['#item_attributes']['class'][] = implode(' ', $img_classes);

  }

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
