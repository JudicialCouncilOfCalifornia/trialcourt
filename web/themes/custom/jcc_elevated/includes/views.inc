<?php

/**
 * @file
 * Views preprocessors.
 */

use Drupal\Component\Utility\Xss;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function jcc_elevated_theme_suggestions_views_view_alter(array &$suggestions, array $variables) {
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
function jcc_elevated_theme_suggestions_views_view_unformatted_alter(array &$suggestions, array $variables) {
  // Add a suggestion based on the view name and the current display.
  $view = $variables['view'];
  $name = $view->id();
  $display = $view->current_display;
  $suggestions[] = 'views_view_unformatted__' . $name;
  $suggestions[] = 'views_view_unformatted__' . $name . '__' . $display;
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function jcc_elevated_theme_suggestions_views_view_fields_alter(array &$suggestions, array $variables) {
  // Add a suggestion based on the view name and the current display.
  $view = $variables['view'];
  $name = $view->id();
  $display = $view->current_display;
  $suggestions[] = 'views_view_fields__' . $name;
  $suggestions[] = 'views_view_fields__' . $name . '__' . $display;
}

/**
 * Implements hook_preprocess_views_view().
 */
function jcc_elevated_preprocess_views_view(&$variables) {
  $view = $variables['view'];
  if (($view->id() == 'search') && ($view->current_display == 'search')) {
    // Set the item count for setting in the title override.
    $variables['count'] = count($view->result);

    // Get the searched term for setting in the title override.
    $searched_term = $view->getExposedInput();
    $variables['searched_term'] = !empty($searched_term['search']) ? Xss::filter($searched_term['search']) : NULL;
    $variables['title'] = $view->getTitle();
  }
}

/**
 * Implements hook_views_pre_render().
 */
function jcc_elevated_views_pre_render(ViewExecutable $view) {
  if (($view->id() == 'search') && ($view->current_display == 'search')) {

    // Hide some fields from display on the search page items.
    // $view->field['search_api_excerpt_1']->options['exclude'] = TRUE;.
    // $view->field['field_heading_1']->options['exclude'] = TRUE;.
  }
}

/**
 * Implements hook_views_pre_render().
 */
function jcc_elevated_views_post_render(ViewExecutable $view) {
  if (($view->id() == 'search') && ($view->current_display == 'search')) {

    // Set our default title for the search page.
    $view->setTitle(t('Search results'));
  }
}

/**
 * Prepares variables for views mini-pager templates.
 */
function jcc_elevated_preprocess_views_mini_pager(&$variables) {
  if (isset($variables['items']['current'])) {
    // Setting up the views mini_pager variables to better match system pager.
    $current = $variables['items']['current'];
    $variables['items']['pages'][$current] = [
      'title' => 'Current page',
      'href' => '',
    ];
  }

  if (isset($variables['items']['previous']['text'])) {
    // Remove previous text for the mini pager.
    $variables['items']['previous']['text'] = '';
  }

  if (isset($variables['items']['next']['text'])) {
    // Remove next text for the mini pager.
    $variables['items']['next']['text'] = '';
  }
}
