<?php

/**
 * @file
 * Preprocess and functions for paragraphs.
 */

use Drupal\Core\Language\LanguageInterface;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_elevated_preprocess_paragraph(&$variables) {
  $paragraph = $variables['paragraph'];
  $function = "jcc_elevated_paragraph_" . $paragraph->bundle();
  if (function_exists($function)) {
    $function($variables, $paragraph);
  }
}

/**
 * Helper function for accordion paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 */
function jcc_elevated_paragraph_accordion(array &$variables) {
  $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  $variables['accordion_items'] = [];

  // Build the array of accordion items.
  if ($variables['paragraph']->hasField('field_components')) {
    $items = $variables['paragraph']->get('field_components')->referencedEntities();

    foreach ($items as $item) {
      $item = \Drupal::service('entity.repository')->getTranslationFromContext($item, $langcode);
      $variables['accordion_items'][] = [
        'label' => $item->hasField('field_heading') ? $item->get('field_heading')->first()->value : '',
        'content' => $item->get('field_text')->view('default'),
      ];
    }
  }
}

/**
 * Helper function for steps paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 */
function jcc_elevated_paragraph_steps(array &$variables) {
  $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  $variables['steps'] = [];

  // Build the array of step items.
  if ($variables['paragraph']->hasField('field_components')) {
    $items = $variables['paragraph']->get('field_components')->referencedEntities();

    foreach ($items as $item) {
      $item = \Drupal::service('entity.repository')->getTranslationFromContext($item, $langcode);
      $variables['steps'][] = [
        'label' => $item->hasField('field_heading') ? $item->get('field_heading')->first()->value : '',
        'content' => $item->get('field_text')->view('default'),
      ];
    }
  }
}

/**
 * Helper function for alert paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 */
function jcc_elevated_paragraph_alert(array &$variables) {
  $variables['type'] = NULL;

  // Get the alert type from the variant field.
  if ($variables['paragraph']->hasField('field_variant')) {
    $items = $variables['paragraph']->get('field_variant')->getValue();
    $variant = json_decode($items[0]['value']);
    if ($variant->alert) {
      $variables['type'] = $variant->alert == 'success' ? 'status' : $variant->alert;
    }
  }
}
