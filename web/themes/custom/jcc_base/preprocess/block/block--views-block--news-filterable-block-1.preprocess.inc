<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_block__views_block__news_filterable_block_1(&$variables) {
  $header_title = empty($variables['elements']['#configuration']['views_label']) ? $variables['elements']['content']['#title']['#markup'] : $variables['elements']['#configuration']['views_label'];

  $cards = [];
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
    $url = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $row->_entity->nid->value);

    $recent_news_card = [
      'brow' => $brow,
      'title' => $title,
      'excerpt' => $excerpt,
      'url' => $url,
      'read_more' => [
        'label' => $read_more_text,
        'url' => $url,
      ],
    ];
    array_push($cards, $recent_news_card);
  }

  if ($variables['elements']['content']['#view']->display_handler->display['display_options']['use_more'] == TRUE) {
    $button_text = $variables['elements']['content']['#view']->display_handler->display['display_options']['use_more_text'];
    $button_url = '/news';

    $button = [
      'text' => $button_text,
      'url' => $button_url,
      'style' => 'primary',
    ];
  }
  else {
    $button = [];
  }

  $variables['my_news_list'] = [
    'headergroup' => [
      'title' => htmlspecialchars_decode($header_title),
    ],
    'cards' => $cards,
    'button' => $button,
  ];
}
