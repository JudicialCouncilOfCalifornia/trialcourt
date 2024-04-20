<?php

/**
 * @file
 * Theme and preprocess functions for pages.
 */

use Drupal\Component\Utility\Html;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_page__search(&$variables) {
  // Personalized no results message.
  $variables['no_results_heading'] = 'We are unable to find a result for your search.';
  if (theme_get_setting('no_search_results_heading')) {
    $variables['no_results_heading'] = theme_get_setting('no_search_results_heading');
  }
  $variables['no_results_message'] = ['#markup' => Html::decodeEntities('Visit our <a href="/">homepage</a> for more options.')];
  $no_results_msg = theme_get_setting('no_search_results_message');
  if ($no_results_msg['value']) {
    $variables['no_results_message'] = ['#markup' => Html::decodeEntities($no_results_msg['value'])];
  }
}
