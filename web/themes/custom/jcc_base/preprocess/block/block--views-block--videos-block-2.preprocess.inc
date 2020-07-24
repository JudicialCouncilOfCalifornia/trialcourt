<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_newsroom_preprocess_block__views_block__videos_block_2(&$variables) {
  $header_title = empty($variables['elements']['#configuration']['views_label']) ? $variables['elements']['content']['#title']['#markup'] : $variables['elements']['#configuration']['views_label'];

  if (isset($variables['elements']['content']['#view'])) {
    $cards = [];
    $rows = $variables['elements']['content']['#view']->result;

    foreach ($rows as $row) {
      $title = $row->_entity->title->value;

      $url = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $row->_entity->nid->value);

      $video_url = $row->_entity->field_media_video->getValue();
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('media');
      $storage = \Drupal::entityTypeManager()->getStorage('media');
      $entity_id = $storage->load($video_url[0]['target_id']);
      $build = $view_builder->view($entity_id, 'default');
      $video_src = render($build);

      $featured_videos_card = [
        'title' => $title,
        'url' => $url,
        'excerpt_vid' => $video_src,
      ];
      array_push($cards, $featured_videos_card);
    }

    $variables['featured_webcast'] = [
      'headergroup' => [
        'title' => htmlspecialchars_decode($header_title),
      ],
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
