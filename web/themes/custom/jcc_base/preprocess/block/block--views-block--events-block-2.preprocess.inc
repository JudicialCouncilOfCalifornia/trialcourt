<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\taxonomy\Entity\Term;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_block__views_block__events_block_2(&$variables) {
  $header_title = empty($variables['elements']['#configuration']['views_label']) ? $variables['elements']['content']['#title']['#markup'] : $variables['elements']['#configuration']['views_label'];
  $cards = [];

  if (isset($variables['elements']['content']['#view'])) {
    $rows = $variables['elements']['content']['#view']->result;

    foreach ($rows as $row) {
      $date = new DrupalDateTime($row->_entity->field_date_range->value, 'UTC');
      $end_date = new DrupalDateTime($row->_entity->field_date_range->end_value, 'UTC');

      // Display only current or upcoming events.
      if ($end_date->getTimestamp() >= \Drupal::time()->getCurrentTime()) {
        $event_type = ($row->_entity->field_event_type->target_id ? Term::load($row->_entity->field_event_type->target_id)->get('name')->value : '');
        $title = $row->_entity->title->value;

        $start_date_only = $date->format('Y-m-d');
        $end_date_only = $end_date->format('Y-m-d');

        if ($start_date_only === $end_date_only) {
          $excerpt = $date->format('M j, Y');
        }
        else {
          $excerpt = $date->format('M j, Y') . ' - ' . $end_date->format('M j, Y');
        }

        $url = Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $row->_entity->nid->value);

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

        $featured_events_card = [
          'brow' => $event_type,
          'title' => $title,
          'excerpt' => $excerpt,
          'url' => $url,
          'contextual_links' => $placeholder,
        ];
        array_push($cards, $featured_events_card);
      }
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

  $variables['events_links'] = [
    'id' => 'jcc-events-header',
    'headergroup' => [
      'title' => htmlspecialchars_decode($header_title),
    ],
  ];
  $variables['events_cards'] = $cards;
  $variables['button'] = $button;
}
