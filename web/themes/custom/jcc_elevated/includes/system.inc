<?php

/**
 * @file
 * Generic system element preprocessors.
 */

/**
 * Implements hook_preprocess_views_view().
 */
function jcc_elevated_preprocess_pager(&$variables) {
  // Add the current value into items for easier theming.
  $variables['items']['current'] = $variables['current'] ?? 0;

  if (isset($variables['items']['first']['text'])) {
    // Lock the text value for the first.
    $variables['items']['first']['text'] = t('First');
    // To remove the "First" link, uncomment below.
    // $variables['items']['first'] = [];.
  }

  if (isset($variables['items']['previous']['text'])) {
    // Lock the text value for the previous.
    $variables['items']['previous']['text'] = t('Previous');
  }

  if (isset($variables['items']['next']['text'])) {
    // Lock the text value for the next.
    $variables['items']['next']['text'] = t('Next');
  }

  if (isset($variables['items']['last']['text'])) {
    // Lock the text value for the last.
    $variables['items']['last']['text'] = t('Last');
    // To remove the "Last" link, uncomment below.
    // $variables['items']['last'] = [];.
  }
}

/**
 * Implements template_preprocess_form_element().
 */
function jcc_elevated_preprocess_form_element(&$variables) {
  $variables['attributes']['class'][] = 'placeholder-container';
}

/**
 * Implements template_preprocess_input().
 */
function jcc_elevated_preprocess_input(&$variables) {
  $variables['element']['#placeholder'] = '';
}

/**
 * Implements template_preprocess_textarea().
 */
function jcc_elevated_preprocess_textarea(&$variables) {
  $variables['element']['#placeholder'] = '';
}
