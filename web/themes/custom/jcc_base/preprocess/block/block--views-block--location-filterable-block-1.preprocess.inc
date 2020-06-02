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

  $cards = array();
  $rows = $variables['elements']['content']['#view']->result;

  foreach ($rows as $row) {
    $title = $row->_entity->title->value;
    $address = \Drupal::service('renderer')->renderRoot($row->_entity->field_location->view('default'));
    $phone = \Drupal::service('renderer')->renderRoot($row->_entity->field_phone->view('default'));
    $office_hours = \Drupal::service('renderer')->renderRoot($row->_entity->field_office_hours->view('default'));
    $phone_hours = \Drupal::service('renderer')->renderRoot($row->_entity->field_phone_hours->view('default'));
    $excerpt = t($address . $office_hours . $phone . $phone_hours);

    $location_card = [
      'title' => $title,
      'excerpt' => $excerpt
    ];
    array_push($cards, $location_card);
  }

  /** create a placeholder third card here until we figure out what we need */
  $note_text = $variables['elements']['#configuration']['contextual_filter']['note_text']['value'];

  $location_card = [
    'excerpt' => $note_text,
  ];

  array_push($cards, $location_card);
  /** end placeholder card */

  if ($variables['elements']['content']['#view']->display_handler->display['display_options']['use_more'] == true) {
    $button_text = $variables['elements']['content']['#view']->display_handler->display['display_options']['use_more_text'];
    $button_url = $variables['elements']['content']['#view']->display_handler->display['display_options']['link_url'];

    $button = [
      'text' => $button_text,
      'url' => $button_url,
      'style' => 'primary'
    ];
  } else {
    $button = [];
  }

  $variables["my_location_list"] = [
    'headergroup' => [
      'title' => htmlspecialchars_decode($header_title),
    ],
    'background_variant' => 'has-background-color--light--secondary',
    'column_variant' => 'has-three-columns',
    'cards' => $cards,
    'button' => $button
  ];
}
