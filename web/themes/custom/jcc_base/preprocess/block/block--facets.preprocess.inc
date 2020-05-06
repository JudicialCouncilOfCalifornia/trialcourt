<?php
/**
 * @file
 * Theme and preprocess functions for blocks.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_block__facets(&$variables) {
  $variables['attributes']['class'][] = 'tablet:grid-col-auto';
  if (isset($variables['content'][0]['#facet']) && $variables['content'][0]['#facet']) {
    $variables['has_facet_results'] = TRUE;
  }
}
