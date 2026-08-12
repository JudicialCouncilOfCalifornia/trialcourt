<?php

/**
 * @file
 * Theme and preprocess functions for suggestions.
 */

/**
 * Implements hook_theme_suggestions_fieldset_alter().
 */
function jcc_newsroom_theme_suggestions_fieldset_alter(array &$suggestions, array $variables) {
  // ADA-330: Use a dedicated template for the "Filter by News Type" exposed
  // filter on the News view so its legend can be promoted to a heading (h2)
  // for an accessible document outline.
  $name = $variables['element']['#name'] ?? '';
  if (strpos($name, 'field_news_type_target_id') === 0) {
    $suggestions[] = 'fieldset__news_type_filter';
  }
}
