<?php

/**
 * @file
 * Functions for page processing.
 */

use Drupal\Core\Extension\ThemeSettingsProvider;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_region__drawer(&$variables) {
  if (\Drupal::service(ThemeSettingsProvider::class)->getSetting('hide_was_this_helpful') === 1) {
    $variables['hide_was_this_helpful'] = \Drupal::service(ThemeSettingsProvider::class)->getSetting('hide_was_this_helpful');
  }
}
