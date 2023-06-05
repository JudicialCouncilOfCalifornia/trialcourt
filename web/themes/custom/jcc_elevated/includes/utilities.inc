<?php

/**
 * @file
 * Functions for views processing.
 */

/**
 * Helper function to load language labels.
 *
 * @return string
 *   Language labels JSON.
 */
function __jcc_language_sets() {
  $data = file_get_contents(\Drupal::service('extension.path.resolver')->getPath('theme', 'jcc_components') . '/jcc-language-labels.json');

  return json_decode($data);
}
