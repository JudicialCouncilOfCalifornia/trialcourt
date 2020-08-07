<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_newsroom_preprocess_block__views_block__news_block_2(&$variables) {
  $cards = [];
  $excerpt = '';

  if (isset($variables['elements']['content']['#view'])) {
    $rows = $variables['elements']['content']['#view']->result;

    foreach ($rows as $row) {
      $title = $row->_entity->title->value;
      if ($row->_entity->body->value != NULL) {
        $excerpt = substr(strip_tags($row->_entity->body->value), 0, 200);
      } else {
        if ($row->_entity->field_attachments != NULL) {
          $excerpt = $row->_entity->field_attachments->entity->getName();
        }
      }
      $url = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $row->_entity->nid->value);
      $img_src = isset($row->_entity->field_images->entity->field_media_image->entity->uri->value) ? file_create_url($row->_entity->field_images->entity->field_media_image->entity->uri->value) : "";
      $img_alt = isset($row->_entity->field_images->entity->field_media_image->alt) ? $row->_entity->field_images->entity->field_media_image->alt : "";

      $moduleHandler = Drupal::service('module_handler');
      $user = Drupal::currentUser();
      $placeholder = FALSE;
      if ($moduleHandler->moduleExists('contextual') && $user->hasPermission('access contextual links')) {
        $contextual_links = [
          'node' => [
            'route_parameters' => [
              'node' => $row->_entity->id(),
            ],
          ],
        ];
        $placeholder = [
          '#type' => 'contextual_links_placeholder',
          '#id' => _contextual_links_to_id($contextual_links),
        ];
      }

      $featured_news_card = [
        'title' => $title,
        'excerpt' => $excerpt,
        'excerpt_img' => [
          'src' => $img_src,
          'alt' => $img_alt,
          'renderer' => 'patternlab',
        ],
        'url' => $url,
        'contextual_links' => $placeholder,
      ];
      array_push($cards, $featured_news_card);
    }
  }

  $variables['featured_news'] = [
    'cards' => $cards,
  ];
}
