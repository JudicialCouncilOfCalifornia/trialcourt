<?php

/**
 * @file
 * Block element preprocessors.
 */

/**
 * Implements hook_preprocess_block().
 */
function jcc_elevated_preprocess_block(&$variables) {
  if ($variables['plugin_id'] == 'views_block:alerts-alerts') {
    $variables['title_prefix'] = [];
    $variables['title_suffix'] = [];
  }
}
