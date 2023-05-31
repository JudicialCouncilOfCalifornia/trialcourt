<?php

/**
 * @file
 * Provides hooks for jcc_components module.
 */

/**
 * Implements hook_theme_suggestions_alter().
 */
function jcc_elevated_theme_suggestions_form_alter(array &$suggestions, array $variables, $hook) {
  if (isset($variables['element']['#id'])) {
    $suggestions[] = $hook . '__' . str_replace('-', '_', $variables['element']['#id']);
  }
}

/**
 * Implements hook_theme_suggestions_alter().
 */
function jcc_elevated_theme_suggestions_alter(array &$suggestions, array $variables, $hook) {
  if (
    isset($variables['element']['#id'])
    && isset($variables['element']['#type'])
    && isset($variables['element']['#name'])
  ) {
    $element = $variables['element'];
    $formid = str_replace('-', '_', $element['#id']);
    $suggestions[] = $hook . '__' . $formid;
    $suggestions[] = $hook . '__' . $element['#type'] . '__' . $formid;
    $suggestions[] = $hook . '__' . $element['#name'] . '__' . $formid;
    $suggestions[] = $hook . '__' . $element['#name'] . '__' . $element['#type'] . '__' . $formid;
  }
}
