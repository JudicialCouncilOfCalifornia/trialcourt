<?php

/**
 * @file
 * Functions for views processing.
 */

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function jcc_components_theme_suggestions_views_exposed_form_alter(array &$suggestions, array $variables) {
  if (strpos($variables['form']['#id'], 'views-exposed-form-') >= 0) {
    $form = str_replace('views-exposed-form-', '', $variables['form']['#id']);
    $form = str_replace('-', '_', $form);

    $suggestions[] = $variables['theme_hook_original'] . '__' . $form;
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function jcc_components_theme_suggestions_views_view_alter(array &$suggestions, array $variables) {
  // Add a suggestion based on the view name and the current display.
  $view = $variables['view'];
  $name = $view->id();
  $display = $view->current_display;
  $suggestions[] = 'views_view__' . $name;
  $suggestions[] = 'views_view__' . $name . '__' . $display;
}

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_views_view(&$variables) {
  $variables['header_footer_variant'] = theme_get_setting('header_footer_variant', 'jcc_components');

  if ($variables['id'] == 'search') {
    $variables['keywords'] = $variables['exposed']['search']['#value'];
    $variables['num_results'] = $variables['view']->total_rows;
  }
}
