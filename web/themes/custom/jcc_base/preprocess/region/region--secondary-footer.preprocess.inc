<?php

/**
 * @file
 * Theme and preprocess functions for region.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_region__secondary_footer(&$variables) {
  $footer_menu = $variables['elements']['secondaryfooter']['content'];

  $variables['list_section']['menu'] = [
    'classes' => [],
    'disable_header_link' => TRUE,
    'items' => $footer_menu['#items']
  ];
}
