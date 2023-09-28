<?php

/**
 * @file
 * Functions for page processing.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_region__drawer(&$variables) {
  if (theme_get_setting('hide_was_this_helpful') === 1) {
    $variables['hide_was_this_helpful'] = theme_get_setting('hide_was_this_helpful');
  }
}
