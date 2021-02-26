<?php

/**
 * @file
 * Theme and preprocess functions for nodes.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_md_preprocess_node(&$variables, $hook) {
  $node = $variables['node'];
  switch ($node->getType()) {
    case 'landing_page':
      $variables['content']['book_navigation'] = '';
      break;
  }
}
