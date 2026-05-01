<?php

/**
 * @file
 * Provides hooks for jcc_components module on html.
 */

use Drupal\node\NodeInterface;

/**
 * Implements hook_theme_suggestions_html_alter().
 */
function jcc_components_theme_suggestions_html_alter(array &$suggestions, array $variables) {
  $current_path = \Drupal::service('path.current')->getPath();
  $alias = \Drupal::service('path_alias.manager')->getAliasByPath($current_path);
  if (str_contains($alias, 'tentative-ruling')) {
    $suggestions[] = 'html__tentative_ruling';
  }
}

/**
 * Implements hook_preprocess_html().
 */
function jcc_components_preprocess_html(array &$variables) {
  $route_match = \Drupal::routeMatch();
  $node = $route_match->getParameter('node');
  if ($node instanceof NodeInterface) {
    if ($node->bundle() === 'tentative_ruling') {
      if ($node->hasField('field_caption') && !$node->get('field_caption')->isEmpty()) {
        $caption = $node->get('field_caption')->value;
        $variables['head_title']['title'] = 'Tentative Ruling: ' . $caption;
      }
    }
  }
}
