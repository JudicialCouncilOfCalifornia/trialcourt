<?php

/**
 * @file
 * Preprocess and functions for paragraphs.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_paragraph(&$variables) {
  $paragraph = $variables['paragraph'];

  if ($paragraph->bundle() == 'hero_banner') {
    // Set up values for icon_links to pass to the pattern library template.
    $components = $paragraph->field_components->referencedEntities();
    foreach ($components as $component) {
      if (!empty($component->field_icon)) {
        $variables['content']['icon_links'][] = [
          'icon' => !empty($component->field_icon->view()[0]) ? $component->field_icon->view()[0] : NULL,
          'link' => !empty($component->field_link->view()[0]) ? $component->field_link->view()[0] : NULL,
        ];
      }
    }
  }
}
