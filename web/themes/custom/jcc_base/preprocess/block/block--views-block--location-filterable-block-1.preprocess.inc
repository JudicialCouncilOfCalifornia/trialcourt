<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_block__views_block__location_filterable_block_1(&$variables) {
  $header_title = empty($variables['elements']['#configuration']['views_label']) ? $variables['elements']['content']['#title']['#markup'] : $variables['elements']['#configuration']['views_label'];

  $cards = [];
  $rows = $variables['elements']['content']['#view']->result;

  foreach ($rows as $row) {
    $location = !empty($row->_entity->field_location) ? $row->_entity->field_location->first() : NULL;
    $location->view([
      'type' => '#markup',
      'label' => 'hidden',
    ]);

    $address = \Drupal::service('renderer')->renderRoot($location->view('default'));
    $phone = $row->_entity->field_phone->value;
    $office_hours = \Drupal::service('renderer')->renderRoot($row->_entity->field_office_hours->view('default'));
    $phone_hours = \Drupal::service('renderer')->renderRoot($row->_entity->field_phone_hours->view('default'));
    $excerpt = t($address . $office_hours . $phone . $phone_hours);

    $location_card = [
      'excerpt' => $excerpt,
    ];
    array_push($cards, $location_card);
  }

  $note_text = $variables['elements']['#configuration']['contextual_filter']['note_text']['value'] ?? '';

  $location_card = [
    'excerpt' => $note_text,
  ];

  array_push($cards, $location_card);

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

  $variables["my_location_list"] = [
    'headergroup' => [
      'title' => htmlspecialchars_decode($header_title),
    ],
    'background_variant' => 'has-background-color--light--secondary',
    'column_variant' => 'has-three-columns',
    'cards' => $cards,
    'button' => $button,
  ];
}
