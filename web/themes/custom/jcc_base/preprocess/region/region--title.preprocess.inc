<?php

/**
 * @file
 * Theme and preprocess functions for region.
 */

/**
 * Implements hook_preproess_HOOK().
 */
function jcc_base_preprocess_region__title(&$variables) {

  // check if a search facet exists
  if (isset($variables['elements']['tags'])) {
    $variables['attributes']['class'][] = 'grid-container-widescreen padding-y-4 region--search-facets';
    $variables['is_search_page'] = TRUE;
  }
}
