<?php

/**
 * @file
 * Preprocess and functions for alert content type and component.
 */

use Drupal\node\NodeInterface;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_node(&$variables) {
  $node = $variables['node'];
  $function = "jcc_components_node_" . $node->bundle();
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
function jcc_components_node_news(array &$variables, NodeInterface $node) {
  $variables['breadcrumb'] = \Drupal::service('breadcrumb')
    ->build(\Drupal::routeMatch())
    ->toRenderable();
}
