<?php
/**
 * @file
 * Theme and preprocess functions for views.
 */

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function jcc_base_theme_suggestions_views_view_alter(array &$suggestions, array $variables) {
  // Add a suggestion based on the view name and the current display.
  $view = $variables['view'];
  $name = $view->id();
  $display = $view->current_display;
  $suggestions[] = 'views_view__' . $name;
  $suggestions[] = 'views_view__' . $name . '__' . $display;
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function jcc_base_theme_suggestions_views_view_unformatted_alter(array &$suggestions, array $variables) {
  // Add a suggestion based on the view name and the current display.
  $view = $variables['view'];
  $name = $view->id();
  $display = $view->current_display;
  $suggestions[] = 'views_view_unformatted__' . $name . '__' . $display;
}

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_views_view(&$variables) {
  $view = $variables['view'];
  switch($view->id()) {
    case 'location':
      if ($view->current_display == 'block') {
        foreach($view->result as $row) {
          $variables['sidebar']['rows'][] = $row->_entity;
        }
        $num_rows = $view->total_rows;
        $verb = $num_rows == 1 ? 'is' : 'are';
        $noun = $num_rows == 1 ? 'Location' : 'Locations';
        $replacements = [
          '@verb' => $verb,
          '@num' => $num_rows,
          '@noun' => $noun,
        ];

        $variables['sidebar']['description'] = t('There @verb @num Court @noun', $replacements);
      }
      break;
  }
}
