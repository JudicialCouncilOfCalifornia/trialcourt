<?php

/**
 * @file
 * Provides hooks for jcc_components module.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Implements hook_theme_suggestions_alter().
 */
function jcc_components_theme_suggestions_alter(array &$suggestions, array $variables, $hook) {
  if (
    isset($variables['element']['#form_id'])
    && isset($variables['element']['#type'])
    && isset($variables['element']['#name'])
  ) {
    $element = $variables['element'];
    $formid = str_replace('-', '_', $element['#form_id']);
    $suggestions[] = $hook . '__' . $formid;
    $suggestions[] = $hook . '__' . $element['#type'] . '__' . $formid;
    $suggestions[] = $hook . '__' . $element['#name'] . '__' . $formid;
    $suggestions[] = $hook . '__' . $element['#name'] . '__' . $element['#type'] . '__' . $formid;
  }
}

/**
 * Implements hook_form_alter().
 */
function jcc_components_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  _jcc_components_attach_form_id($form, $form_id);
}

/**
 * Implements hook_preprocess_form_element().
 */
function jcc_components_preprocess_form_element(array &$variables) {
  if (isset($variables['label']) && isset($variables['element']['#form_id'])) {
    $variables['label']['#form_id'] = $variables['element']['#form_id'];
  }
  if (isset($variables['label']) && isset($variables['element']['#type'])) {
    $variables['label']['#form_element_type'] = $variables['element']['#type'];
  }
}

/**
 * Attaches form id to all form elements.
 *
 * @param array $form
 *   The form or form element which children should have form id attached.
 * @param string $form_id
 *   The form id attached to form elements.
 */
function _jcc_components_attach_form_id(array &$form, $form_id) {
  foreach (Element::children($form) as $child) {
    if (!isset($form[$child]['#form_id'])) {
      $form[$child]['#form_id'] = $form_id;
    }
    _jcc_components_attach_form_id($form[$child], $form_id);
  }
}
