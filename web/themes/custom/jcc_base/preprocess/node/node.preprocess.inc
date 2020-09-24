<?php

/**
 * @file
 * Theme and preprocess functions for nodes.
 */

use Drupal\views\Views;
use Drupal\Core\Url;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_node(&$variables, $hook) {
  $node = $variables['node'];
  switch ($node->getType()) {
    case 'location':
      if ($variables['view_mode'] == 'map_card') {
        $location = !empty($node->field_location) ? $node->field_location->first() : NULL;
        $address = [
          $location->address_line1,
          $location->locality,
          $location->administrative_area,
          $location->postal_code,
        ];
        $query = implode('%20', $address);
        $variables['directions_url'] = "https://bing.com/maps/default.aspx?where1=" . $query;
        $variables['content']['field_subject_matter']['#label_display'] = 'hidden';
      }
      elseif ($variables['view_mode'] == 'teaser_secondary') {
        $address = [
          $variables['content']['field_location'][0]['address_line1']['#value'],
          $variables['content']['field_location'][0]['address_line2']['#value'],
          ",",
          $variables['content']['field_location'][0]['locality']['#value'],
          $variables['content']['field_location'][0]['administrative_area']['#value'],
          $variables['content']['field_location'][0]['postal_code']['#value'],
        ];
        $variables['content']['formatted_address'] = implode(" ", $address);
        $subjects = $node->field_subject_matter->referencedEntities();
        $variables['content']['subject_matter'] = [];
        foreach ($subjects as $key => $subject) {
          $variables['content']['subject_matter'][] = [
            'text' => $subject->getName(),
          ];
        }
        if ((isset($variables['content']['field_office_hours'][0]) && $variables['content']['field_office_hours'][0])
          || (isset($variables['content']['field_phone_hours'][0]) && $variables['content']['field_phone_hours'][0])) {
          $variables['content']['hours_formatted'] = [];
          if ($variables['content']['field_office_hours']) {
            $variables['content']['hours_formatted'][] = [
              'label' => $variables['content']['field_office_hours']['#title'],
              'details' => $variables['content']['field_office_hours'][0],
            ];
          }
          if ($variables['content']['field_phone_hours']) {
            $variables['content']['hours_formatted'][] = [
              'label' => $variables['content']['field_phone_hours']['#title'] ?? '',
              'details' => $variables['content']['field_phone_hours'][0] ?? '',
            ];
          }
        }
        else {
          $variables['content']['hours_formatted'] = NULL;
        }
      }
      break;

    case 'faq':
      if ($variables['view_mode'] == 'accordion_item') {
        foreach ($node->getFields() as $field_name => $field) {
          $field_settings = $field->getSettings();
          if (!empty($field_settings['target_type']) && $field_settings['target_type'] == 'taxonomy_term') {
            $terms = $field->referencedEntities();
            if (!empty($terms)) {
              $field_definition = $field->getFieldDefinition();
              $variables['vocabularies'][$field_name]['link_list']['header'] = $field_definition->getLabel();
              foreach ($terms as $term) {
                $url = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $term->id()]);
                $variables['vocabularies'][$field_name]['link_list']['items'][] = [
                  'url_title' => $term->getName(),
                  'url' => $url,
                ];
              }
            }
          }
        }
      }
      break;

    case 'book':
      if ($variables['view_mode'] == 'full') {
        $topics = $node->field_topics->referencedEntities();
        $divisions = $node->field_division->referencedEntities();
        $termIds = '';
        $divisionIds = '';
        if (!empty($topics)) {
          foreach ($topics as $term) {
            if ($termIds == '') {
              $termIds = $term->id();
            }
            else {
              $termIds = $termIds . '+' . $term->id();
            }
          }
        }
        if (!empty($divisions)) {
          foreach ($divisions as $division) {
            if ($divisionIds == '') {
              $divisionIds = $division->id();
            }
            else {
              $divisionIds = $divisionIds . '+' . $division->id();
            }
          }
        }

        $view = Views::getView('faq');
        $view->setDisplay('block_1');
        $view->setArguments([$termIds, $divisionIds]);
        $variables['faq_items'] = $view->render();
      }
  }

  // Optionally, run node-type-specific preprocess functions, like
  // foo_preprocess_node_page() or foo_preprocess_node_story().
  $function = __FUNCTION__ . '_' . $node->getType();
  if (function_exists($function)) {
    $function($variables, $hook);
  }
}
