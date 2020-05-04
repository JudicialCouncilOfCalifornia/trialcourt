<?php
/**
 * @file
 * Theme and preprocess functions for nodes.
 */

use Drupal\Core\Url;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_node(&$variables) {
  $node = $variables['node'];
  switch ($node->getType()) {
    case 'location':
      if ($variables['view_mode'] == 'map_card') {
        $location = !empty($node->field_location) ? $node->field_location->first() : NULL;
        $address = [
          $location->address_line1,
          $location->locality,
          $location->administrative_area,
          $location->postal_code
        ];
        $query = implode('%20', $address);
        $variables['directions_url'] = "https://bing.com/maps/default.aspx?where1=" . $query;
        $variables['content']['field_subject_matter']['#label_display'] = 'hidden';
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
                $variables['vocabularies'][$field_name]['link_list']['items'][] =[
                    'url_title' => $term->getName(),
                    'url' => $url
                ];
              }
            }
          }
        }
      }
      break;
  }
}
