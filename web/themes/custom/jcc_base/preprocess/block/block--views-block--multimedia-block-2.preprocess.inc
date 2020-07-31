<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

use Drupal\media\Entity\Media;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_newsroom_preprocess_block__views_block__multimedia_block_2(&$variables) {
  $header_title = empty($variables['elements']['#configuration']['views_label']) ? $variables['elements']['content']['#title']['#markup'] : $variables['elements']['#configuration']['views_label'];

  if (isset($variables['elements']['content']['#view'])) {
    $cards = [];
    $rows = $variables['elements']['content']['#view']->result;

    foreach ($rows as $row) {
      $url = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $row->_entity->nid->value);

      $video = $row->_entity->field_multimedia_source->getValue();
      // Get the video entity using target_id from media field.
      $video_entity = Media::load($video[0]["target_id"]);
      // Video title.
      $video_title = $video_entity->label();
      // Video embed.
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('media');
      $build = $view_builder->view($video_entity, 'default');
      $video_src = render($build);

      $featured_videos_card = [
        'title' => $video_title,
        'url' => $url,
        'excerpt_vid' => $video_src,
      ];
      array_push($cards, $featured_videos_card);
    }

    $variables['featured_webcast'] = [
      'headergroup' => [
        'title' => htmlspecialchars_decode($header_title),
      ],
      'highlight_first' => TRUE,
      'cards' => $cards,
    ];
  }
  else {
    $variables['featured_webcast'] = [
      'headergroup' => [
        'title' => htmlspecialchars_decode($header_title),
      ],
    ];
  }
}
