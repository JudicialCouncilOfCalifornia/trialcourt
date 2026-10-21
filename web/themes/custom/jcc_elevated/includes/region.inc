<?php

/**
 * @file
 * Functions for page processing.
 */

use Drupal\Core\Extension\ThemeSettingsProvider;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_elevated_preprocess_region__drawer(&$variables) {
  // Webform title.
  $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('was_this_helpful');
  $variables['canvas_heading'] = $webform->label();

  if (\Drupal::service(ThemeSettingsProvider::class)->getSetting('hide_was_this_helpful') === 1) {
    $variables['hide_was_this_helpful'] = \Drupal::service(ThemeSettingsProvider::class)->getSetting('hide_was_this_helpful');
  }
}
