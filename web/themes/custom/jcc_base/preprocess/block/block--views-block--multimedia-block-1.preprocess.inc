<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

use Drupal\media\Entity\Media;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_block__views_block__multimedia_block_1(&$variables) {
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

    if ($variables['elements']['content']['#view']->display_handler->display['display_options']['use_more'] == TRUE) {
      $button_text = $variables['elements']['content']['#view']->display_handler->display['display_options']['use_more_text'];
      $button_url = '/multimedia';

      $button = [
        'text' => $button_text,
        'url' => $button_url,
        'style' => 'primary',
      ];
    }
    else {
      $button = [];
    }

    $variables['featured_videos'] = [
      'headergroup' => [
        'title' => htmlspecialchars_decode($header_title),
      ],
      'highlight_first' => TRUE,
      'cards' => $cards,
      'button' => $button,
    ];
  }
  else {
    $variables['featured_videos'] = [
      'headergroup' => [
        'title' => htmlspecialchars_decode($header_title),
      ],
    ];
  }
}
