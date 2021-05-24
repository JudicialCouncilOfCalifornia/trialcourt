<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_deprep_preprocess_block__views_block__events_filterable_block_1(&$variables) {
  $headerTitle = empty($variables['elements']['#configuration']['views_label']) ? $variables['elements']['content']['#title']['#markup'] : $variables['elements']['#configuration']['views_label'];
  $title = '';
  $tiles = [];
  $excerpt = '';
  $rows = $variables['elements']['content']['#view']->result;

  foreach ($rows as $row) {
    if ($row->_entity->body->value != NULL) {
      $excerpt = substr(strip_tags($row->_entity->body->value), 0, 600);
    }
    $url = Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $row->_entity->nid->value);
    $startDate = $row->_entity->field_date_range->value;
    $imgSrc = isset($row->_entity->field_images->entity->field_media_image->entity->uri->value) ? file_create_url($row->_entity->field_images->entity->field_media_image->entity->uri->value) : "";
    $imgAlt = isset($row->_entity->field_images->entity->field_media_image->alt) ? $row->_entity->field_images->entity->field_media_image->alt : "";

    $recentEventsCard = [
      'card' => [
        'title' => $title,
        'excerpt' => $excerpt,
        'url' => $url,
      ],
      'date' => $startDate,
      'img' => [
        'src' => $imgSrc,
        'alt' => $imgAlt,
        'renderer' => 'patternlab',
      ],
    ];

    array_push($tiles, $recentEventsCard);
  }

  $button = [];
  if ($variables['elements']['content']['#view']->display_handler->display['display_options']['use_more'] == TRUE) {
    $buttonText = $variables['elements']['content']['#view']->display_handler->display['display_options']['use_more_text'];
    $buttonUrl = '/calendar';

    $button = [
      'text' => $buttonText,
      'url' => $buttonUrl,
      'style' => 'primary',
    ];
  }

  $variables['my_events_list'] = [
    'headergroup' => [
      'title' => htmlspecialchars_decode($headerTitle),
    ],
    'tiles' => $tiles,
    'button' => $button,
  ];
}
