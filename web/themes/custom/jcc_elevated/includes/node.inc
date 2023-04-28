<?php

/**
 * @file
 * Preprocess and functions for alert content type and component.
 */

use Drupal\node\NodeInterface;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_elevated_preprocess_node(&$variables) {
  $node = $variables['node'];
  $function = "jcc_elevated_node_" . $node->bundle();
  if (function_exists($function)) {
    $function($variables, $node);
  }
}

/**
 * Node: News preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_elevated_node_news(array &$variables, NodeInterface $node) {
  $variables['breadcrumb'] = \Drupal::service('breadcrumb')
    ->build(\Drupal::routeMatch())
    ->toRenderable();

  // Render and truncate field_components.
  $render_array = $node->field_components->view('default');
  $output = \Drupal::service('renderer')->render($render_array);
  $variables['components_teaser'] =
    mb_strimwidth(trim(strip_tags($output)), 0, 250, '...');
}
