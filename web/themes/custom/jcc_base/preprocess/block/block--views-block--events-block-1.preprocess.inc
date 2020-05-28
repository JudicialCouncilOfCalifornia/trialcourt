<?php
/**
 * @file
 * Theme and preprocess functions for blocks.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_block__views_block__events_block_1(&$variables) {
  $header_title = empty($variables['elements']['#configuration']['views_label']) ? $variables['elements']['content']['#title']['#markup'] : $variables['elements']['#configuration']['views_label'];

  $tiles = array();
  $rows = $variables['elements']['content']['#view']->result;

  foreach ($rows as $row) {
    $title = $row->_entity->title->value;
    $excerpt = substr(strip_tags($row->_entity->body->value), 1,600);
    $url = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $row->_entity->nid->value);
    $start_date = $row->_entity->field_date_range->value;
    $img_src =isset($row->_entity->field_images->entity->field_media_image->entity->uri->value) ? file_create_url($row->_entity->field_images->entity->field_media_image->entity->uri->value) : "";
    $img_alt = $row->_entity->field_images->entity->field_media_image->alt;

    $recent_events_card = [
      'card' => [
        'title' => $title,
        'excerpt' => $excerpt,
        'url' => $url
      ],
      'date' => $start_date,
      'img' => [
        'src' => $img_src,
        'alt' => $img_alt,
        'renderer' => 'patternlab',
      ]
    ];
    array_push($tiles, $recent_events_card);
  }

  if ($variables['elements']['content']['#view']->display_handler->display['display_options']['use_more'] == true) {
    $button_text = $variables['elements']['content']['#view']->display_handler->display['display_options']['use_more_text'];
    $button_url = '/event';

    $button = [
      'text' => $button_text,
      'url' => $button_url,
      'style' => 'primary'
    ];
  } else {
    $button = [];
  }

  $variables['my_events_list'] = [
    'headergroup' => [
      'title' => htmlspecialchars_decode($header_title),
    ],
    'tiles' => $tiles,
    'button' => $button
  ];
}
