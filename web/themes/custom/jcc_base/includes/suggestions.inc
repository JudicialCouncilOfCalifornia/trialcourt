<?php

/**
 * @file
 * Preprocess suggestions.
 */

use Drupal\node\Entity\Node;

/**
 * Implements hook_theme_suggestions_page_alter().
 */
function jcc_base_theme_suggestions_page_alter(array &$suggestions, array $variables) {
  // Add a template suggestions for node type.
  /** @var \Drupal\node\NodeInterface $node */
  if ($node = \Drupal::routeMatch()->getParameter('node')) {
    if (is_numeric($node)) {
      $node = Node::load($node);
    }

    $suggestions[] = 'page__node__' . $node->bundle();
  }

  // Get Request Object.
  $request = \Drupal::request();

  // If there is an HTTP Exception.
  if ($exception = $request->attributes->get('exception')) {
    // Get the status code.
    $statusCode = $exception->getStatusCode();
    if (in_array($statusCode, [401, 403, 404])) {
      $suggestions[] = 'page__' . $statusCode;
    }
  }
}

/**
 * Implements hook_theme_suggestions_user_alter().
 */
function jcc_base_theme_suggestions_user_alter(array &$suggestions, array $variables) {
  // Add a suggestion based on the view mode.
  $suggestions[] = $variables['theme_hook_original'] . '__' . $variables['elements']['#view_mode'];
}

/**
 * Implements hook_theme_suggestions_region_alter().
 */
function jcc_base_theme_suggestions_region_alter(array &$suggestions, array $variables) {
  // Add a suggestion based on the view mode.
  $suggestions[] = $variables['theme_hook_original'] . '__' . $variables['elements']['#region'];
}
