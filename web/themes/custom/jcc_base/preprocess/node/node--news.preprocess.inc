<?php

/**
 * @file
 * Theme and preprocess functions for nodes.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_node__news(&$variables, $hook) {
  $moduleHandler = \Drupal::service('module_handler');
  $variables['share_buttons_available'] = $moduleHandler->moduleExists('better_social_sharing_buttons');
}
