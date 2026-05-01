<?php

/**
 * @file
 * Provides hooks for jcc_components module on html.
 */

use Drupal\node\NodeInterface;


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
