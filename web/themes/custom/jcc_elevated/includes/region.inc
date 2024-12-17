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
