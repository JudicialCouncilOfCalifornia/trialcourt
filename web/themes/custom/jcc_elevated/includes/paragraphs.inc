<?php

/**
 * @file
 * Preprocess and functions for paragraphs.
 */

use Drupal\Core\Language\LanguageInterface;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_elevated_preprocess_paragraph(&$variables, $hook) {
  $paragraph = $variables['paragraph'];
  $function = __FUNCTION__ . '__' . $paragraph->bundle();
  if (function_exists($function)) {
    $function($variables, $hook, $paragraph);
  }
}

/**
 * Helper function for accordion paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 */
function jcc_elevated_preprocess_paragraph__accordion(array &$variables) {
  $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  $paragraph = $variables['paragraph'];
  $variables['accordion_items'] = [];

  // Build the array of accordion items.
  if ($paragraph->hasField('field_components')) {
    $items = $paragraph->get('field_components')->referencedEntities();

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
function jcc_elevated_preprocess_paragraph__steps(array &$variables) {
  $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  $paragraph = $variables['paragraph'];
  $variables['steps'] = [];

  // Build the array of accordion items.
  if ($paragraph->hasField('field_components')) {
    $items = $paragraph->get('field_components')->referencedEntities();

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
 * Helper function for iframe paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 */
function jcc_elevated_preprocess_paragraph__iframe(array &$variables) {
  $paragraph = $variables['paragraph'];
  $variables['iframe_url'] = '';
  $variables['iframe_title'] = '';
  $variables['iframe_aspect_ratio'] = '4 / 3';

  // Build the array of accordion items.
  if ($paragraph->hasField('field_link')) {
    $link = $paragraph->get('field_link')->getValue();
    $variables['iframe_url'] = $link[0]['uri'];
    $variables['iframe_title'] = $link[0]['title'];
  }

  if ($paragraph->hasField('field_aspect_ratio')) {
    $aspect_ratio = $paragraph->get('field_aspect_ratio')->getValue();
    switch ($aspect_ratio[0]['value']) {
      case 'video':
        $variables['iframe_aspect_ratio'] = '16 / 9';
        break;

      case 'classic':
        $variables['iframe_aspect_ratio'] = '3 / 4';
        break;

      case 'square':
        $variables['iframe_aspect_ratio'] = '1 / 1';
        break;

      case 'standard':
      default:
        $variables['iframe_aspect_ratio'] = '4 / 3';
        break;

    }
  }
}

/**
 * Helper function for "Layout section" paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 */
function jcc_elevated_preprocess_paragraph__section(array &$variables) {
  $paragraph = $variables['paragraph'];
  $variables['variant'] = 'default';

  if ($paragraph->hasField('field_background')) {
    $background_values = $paragraph->get('field_background')->getValue();
    if (isset($background_values[0]['value'])) {
      $variant = $background_values[0]['value'];

      // The variant class from the existing background field doesn't match the
      // classes needed for sections. Original classes were for cards. We switch
      // out the class value for what we need for the sections.
      switch ($variant) {
        case 'striped-primary-dark-xx':
          $variables['variant_class'] = 'color-primary-dark-xx-stripe';
          break;

        case 'striped-primary-dark-x':
          $variables['variant_class'] = 'primary-dark-x-stripe';
          break;

        case 'striped-primary-light-xx':
          $variables['variant_class'] = 'primary-light-xx-stripe';
          break;

        case 'striped-secondary-dark-xx':
          $variables['variant_class'] = 'secondary-dark-xx-stripe';
          break;

        case 'striped-secondary':
          $variables['variant_class'] = 'secondary-stripe';
          break;

        case 'striped-accent-warm-dark-x':
          $variables['variant_class'] = 'color-warm-dark-x';
          break;

        case 'striped-base-dark-x':
          $variables['variant_class'] = 'base-dark-xx-stripe';
          break;

        default:
          break;

      }
    }
  }
}

/**
 * Helper function for alert paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 */
function jcc_elevated_preprocess_paragraph__alert(array &$variables) {
  $paragraph = $variables['paragraph'];
  $variables['type'] = NULL;

  // Get the alert type from the variant field.
  if ($paragraph->hasField('field_variant')) {
    $items = $paragraph->get('field_variant')->getValue();
    $variant = json_decode($items[0]['value']);
    if ($variant->alert) {
      switch ($variant->alert) {
        case "success":
          $variables['type'] = 'status';
          break;

        default:
          $variables['type'] = $variant->alert;
          break;

      }
    }
  }
}
