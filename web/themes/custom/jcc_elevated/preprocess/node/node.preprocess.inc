<?php

/**
 * @file
 * Theme and preprocess functions for nodes.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_elevated_preprocess_node(&$variables, $hook) {
  $variables['#attached']['library'][] = 'jcc_elevated/extlinkcustom';
}
