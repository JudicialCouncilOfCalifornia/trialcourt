<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_newsroom_preprocess_block__views_block__news_block_4(&$variables) {
  $header_title = empty($variables['elements']['#configuration']['views_label']) ? $variables['elements']['content']['#title']['#markup'] : $variables['elements']['#configuration']['views_label'];

  $links = [];
  $rows = $variables['elements']['content']['#view']->result;

  foreach ($rows as $row) {
    $title = $row->_entity->title->value;
    $url = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $row->_entity->nid->value);

    $newslinks_link = [
      'url_title' => $title,
      'url' => $url,
    ];
    array_push($links, $newslinks_link);
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

  $variables['news_links'] = [
    'header' => htmlspecialchars_decode($header_title),
    'items' => $links,
    'button' => $button,
    'columns' => 2,
  ];
}
