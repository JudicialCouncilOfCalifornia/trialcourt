<?php
/**
 * @file
 * Theme and preprocess functions for nodes.
 */

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

  }
}
