<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_block__views_block__from_the_courts_block_1(&$variables) {
  $header_title = empty($variables['elements']['#configuration']['views_label']) ? $variables['elements']['content']['#title']['#markup'] : $variables['elements']['#configuration']['views_label'];
  $cards = [];

  if (isset($variables['elements']['content']['#view'])) {
    $rows = $variables['elements']['content']['#view']->result;

    foreach ($rows as $row) {
      $feed_source = $row->_entity->field_feed_source->value;
      $title = $row->_entity->title->value;
      $excerpt = date("M j, Y", $row->_entity->created->value);
      $url = $row->_entity->field_links->uri;

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
        'brow' => $feed_source,
        'title' => $title,
        'excerpt' => $excerpt,
        'url' => $url,
        'contextual_links' => $placeholder,
      ];
      array_push($cards, $featured_news_card);
    }
  }

  if ($variables['elements']['content']['#view']->display_handler->display['display_options']['use_more'] == TRUE) {
    $button_text = $variables['elements']['content']['#view']->display_handler->display['display_options']['use_more_text'];
    $button_url = $variables['elements']['content']['#view']->display_handler->display['display_options']['link_url'];

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
    'id' => 'jcc-newslinks-header',
    'headergroup' => [
      'title' => htmlspecialchars_decode($header_title),
    ],
  ];
  $variables['newslinks_cards'] = $cards;
  $variables['button'] = $button;
}
