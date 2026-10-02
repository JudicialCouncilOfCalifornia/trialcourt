<?php

/**
 * @file
 * Functions for page processing.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_elevated_preprocess_region__drawer(&$variables) {
  // Webform title.
  $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('was_this_helpful');
  $variables['canvas_heading'] = $webform->label();

  if (theme_get_setting('hide_was_this_helpful') === 1) {
    $variables['hide_was_this_helpful'] = theme_get_setting('hide_was_this_helpful');
  }
}

/**
 * Implements hook_preprocess_HOOK() for webforms.
 */
function jcc_elevated_preprocess_webform(&$variables) {
  if (empty($variables['element']['#webform_id'])) {
    return;
  }

  $webform_id = $variables['element']['#webform_id'];

  if ($webform_id !== 'was_this_helpful') {
    $variables['#attached']['library'][] = 'jcc_elevated/webform';
  }
}
