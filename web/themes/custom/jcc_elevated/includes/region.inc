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
}
