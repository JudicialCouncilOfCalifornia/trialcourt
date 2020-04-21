<?php

/**
 * Implements hook_preprocess_input().
 */
function jcc_base_preprocess_input(&$variables) {
  $element = $variables['element'];

  // Add the element type to the theme.
  $variables['type'] = $element['#type'];
}
