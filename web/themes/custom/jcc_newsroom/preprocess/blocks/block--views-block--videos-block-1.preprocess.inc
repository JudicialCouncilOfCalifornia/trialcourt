<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_newsroom_preprocess_block__views_block__videos_block_1(&$variables) {
  $header_title = empty($variables['elements']['#configuration']['views_label']) ? $variables['elements']['content']['#title']['#markup'] : $variables['elements']['#configuration']['views_label'];

  $tiles = [];
  $excerpt = '';
  $rows = $variables['elements']['content']['#view']->result;
  $read_more_text = isset($variables['elements']['content']['#view']->field['view_node']->last_render_text) ? $variables['elements']['content']['#view']->field['view_node']->last_render_text : "";

  foreach ($rows as $row) {
    $brow = date("M j, Y", $row->_entity->created->value);
    $title = $row->_entity->title->value;
    if ($row->_entity->body->value != NULL) {
      $excerpt = substr(strip_tags($row->_entity->body->value), 0, 200);
    }
    else {
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

    $recent_news_tile = [
      'card' => [
        'brow' => $brow,
        'title' => $title,
        'excerpt' => $excerpt,
        'url' => $url,
      ],
      'img' => [
        'src' => $img_src,
        'alt' => $img_alt,
        'renderer' => 'patternlab',
      ],
      'contextual_links' => $placeholder,
    ];
    array_push($tiles, $recent_news_tile);
  }

  //if ($variables['elements']['content']['#view']->display_handler->display['display_options']['use_more'] == TRUE) {
    //$button_text = $variables['elements']['content']['#view']->display_handler->display['display_options']['use_more_text'];
    $button_text = 'More Multimedia';
    $button_url = '/multimedia';

    $button = [
      'text' => $button_text,
      'url' => $button_url,
      'style' => 'primary',
    ];
  //}
  //else {
  //  $button = [];
  //}

  $variables['featured_videos'] = [
    'headergroup' => [
      'title' => htmlspecialchars_decode($header_title),
    ],
    'tiles' => $tiles,
    'button' => $button,
  ];
}
