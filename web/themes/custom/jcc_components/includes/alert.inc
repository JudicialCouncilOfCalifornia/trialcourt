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
  if ($node->getType() == 'alert') {

    $variables['show_content'] = FALSE;
    // If should only show on front page, check if this is front page.
    if ($node->field_switch->first()->value) {
      if (\Drupal::service('path.matcher')->isFrontPage()) {
        $variables['show_content'] = TRUE;
        return;
      }
      else {
        return;
      }
    }
    // Check if we're on an allowed page.
    $current = \Drupal::routeMatch()->getParameter('node');
    if ($current instanceof NodeInterface) {
      $references = $node->field_node_reference->referencedEntities();
      // Not front page only, and no specific references, show everywhere.
      if (empty($references)) {
        $variables['show_content'] = TRUE;
        return;
      }
      // Otherwise check specific pages to show alert on.
      foreach ($references as $ref) {
        if ($current->id() == $ref->id()) {
          $variables['show_content'] = TRUE;
          return;
        }
      }
      // Also show if this is the alert's node view page.
      if ($current->id() == $node->id()) {
        $variables['show_content'] = TRUE;
      }
    }
  }
}
