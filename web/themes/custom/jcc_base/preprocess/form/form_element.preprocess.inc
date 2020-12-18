<?php

/**
 * @file
 * Preprocess form element.
 */

/**
 * Implements hook_preprocess_form_element().
 */
function jcc_base_preprocess_form_element(&$variables) {
  $element = $variables['element'];

  // Add required class for checkbox and radio labels.
  if (in_array($element['#type'], ['checkbox', 'radio'])) {
    $variables['label']['#attributes']['class'][] = 'form-check-label';
  }
  if (in_array($element['#type'], ['checkbox'])) {
    if (str_contains($variables['name'], 'field_mailing_group')) {
      $variables['label']['#attributes'] = ['class' => ['usa-checkbox__label']];
    }
  }
}
