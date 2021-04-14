<?php

/**
 * @file
 * Preprocess and functions for alert content type and component.
 */

use Drupal\Core\Menu\MenuTreeParameters;
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
 * Node: Alert preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_components_node_alert(array &$variables, NodeInterface $node) {
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

/**
 * Node: Subpage preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 */
function jcc_components_node_subpage(array &$variables) {
  // Populate the tertiary menu.
  $menu_name = 'main';
  $parameters = new MenuTreeParameters();
  $parameters->onlyEnabledLinks();
  // We only want items at second and thrid levels.
  $parameters->setMinDepth(2);
  $parameters->setMaxDepth(3);
  $menu_active_trail = \Drupal::service('menu.active_trail')
    ->getActiveTrailIds($menu_name);
  $parameters->setActiveTrail($menu_active_trail);
  // Load the tree.
  $tree = \Drupal::menuTree()->load($menu_name, $parameters);

  foreach ($tree as $content) {
    // We only want links for the secondary active trail.
    if ($content->inActiveTrail) {
      foreach ($content->subtree as $sub_content) {
        // If we're in the third level, add the parent for link to second level.
        if ($sub_content->inActiveTrail) {
          $variables['tertiary_menu']['parent'] = [
            'title' => $content->link->getTitle(),
            'url' => $content->link->getUrlObject(),
          ];
        }

        $variables['tertiary_menu']['items'][] = [
          'title' => $sub_content->link->getTitle(),
          'url' => $sub_content->link->getUrlObject(),
          'in_active_trail' => $sub_content->inActiveTrail,
        ];
      }
    }
  }

  // Breadcrumbs get displayed on subpages.
  $variables['breadcrumb'] = \Drupal::service('breadcrumb')
    ->build(\Drupal::routeMatch())
    ->toRenderable();
}

/**
 * Node: location preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node object.
 */
function jcc_components_node_location(array &$variables, NodeInterface $node) {
  // Format the hours content in a format the PL template can recieve.
  if (!empty($node->field_components)) {
    $components = $node->field_components->referencedEntities();
    foreach ($components as $component) {
      $label = '';
      if (!empty($component->field_heading) && !$component->field_heading->isEmpty()) {
        $label = $component->field_heading->first()->view();
      }
      $details = '';
      if (!empty($component->field_hours)) {
        $details = $component->field_hours->view('default');
      }
      $variables['hours'][] = [
        'label' => $label,
        'details' => $details,
      ];
    }
  }
  // Format the address for the directions link.
  if (!empty($node->field_address)) {
    $address = $node->field_address->view();
    // No label; no country.
    $address['#label_display'] = 'hidden';
    $address[0]['country']['#value'] = '';
    $address = render($address);
    $address = trim(strip_tags($address));
    $address = str_replace(PHP_EOL, '<br />', $address);
    $variables['processed_address'] = [
      '#type' => 'processed_text',
      '#text' => $address,
      '#format' => 'basic_html',
    ];
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
