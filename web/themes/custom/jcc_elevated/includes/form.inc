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

/**
 * Implements hook_preprocess_form().
 */
function jcc_elevated_preprocess_container(&$variables) {
  if (isset($variables['attributes']['id'])) {
    switch ($variables['attributes']['id']) {
      case 'edit-actions--4':
        // If hidden submit actions add no spacing class.
        if (str_contains($variables['children'], 'data-bef-auto-submit-click="" class="js-hide')) {
          $variables['attributes']['class'][] = 'no-spacing';
        }
        break;
    }
  }
}

/**
 * Implements hook_preprocess_form().
 */
function jcc_elevated_preprocess_form(&$variables) {
  if ($variables['attributes']['id'] == 'openid-connect-login-form') {
    // OpenID's placement setting to show/hide Drupal login context.
    $user_login_display = \Drupal::config('openid_connect.settings')->get('user_login_display');
    if ($user_login_display) {
      $variables['user_login_display'] = $user_login_display;
    }
  }
}

/**
 * Implements hook_preprocess_preprocess_fieldset().
 */
function jcc_elevated_preprocess_fieldset(&$variables): void {
  // Remove empty tag markup if no BEF checkboxes available.
  $selector_name = $variables['attributes']['data-drupal-selector'];
  if ($selector_name && $selector_name == 'edit-category-group') {
    $cleanedMarkup = str_replace(["\r\n", "\r", "\n"], '', $variables['children']);
    $regex = '/<div(.*?)/';
    preg_match($regex, $cleanedMarkup, $has_children);
    if (!$has_children) {
      $variables['children'] = NULL;
    }
  }
}

/**
 * Implements hook_preprocess_preprocess_details().
 */
function jcc_elevated_preprocess_details(&$variables): void {
  // Remove empty tag markup if no BEF checkboxes available.
  $selector_name = $variables['attributes']['data-drupal-selector'];
  if ($selector_name && str_ends_with($selector_name, '-collapsible')) {
    $cleanedMarkup = str_replace(["\r\n", "\r", "\n"], '', $variables['children']);
    $regex = '/<div class=\"form-checkboxes bef-checkboxes\">(.*?)<\/div>/';
    preg_match($regex, $cleanedMarkup, $has_children);
    if ($has_children[1] && !trim($has_children[1])) {
      $variables['children'] = NULL;
    }
  }
}
