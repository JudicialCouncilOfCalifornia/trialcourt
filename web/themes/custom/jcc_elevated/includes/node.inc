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

/**
 * Node: Subpage preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_elevated_node_subpage(array &$variables, NodeInterface $node) {

  // If the first paragraph on the subpage is a hero, move it out to the header
  // variable, and hide it from the default content array. We want to inject
  // the sidebar navigation between the hero and the rest of the content.
  $variables['header'] = FALSE;
  $component_items = $node->get('field_components')->referencedEntities();
  $bundle = $component_items[0]->bundle();
  if ($bundle == 'hero' || $bundle == 'hero_banner'|| $bundle == 'hero_hangover') {
    $variables['header'] = $node->get('field_components')->first()->view('default');
    $variables['content']['field_components'][0]['#access'] = FALSE;
  }

  // Build out the relevant menu tree. This depends on the current page being in
  // a menu and only on subpages.
  $variables['sidebar_navigation'] = FALSE;
  $menu_name = 'main';
  $level = 2;

  // Find the menu item corresponding to the entity (node).
  $menu_link_service = \Drupal::getContainer()->get('plugin.manager.menu.link');
  $route_params = ['node' => $node->id()];
  $menu_links = $menu_link_service->loadLinksByRoute('entity.node.canonical', $route_params, $menu_name);
  if (!empty($menu_links)) {

    // Build the active trail and menu parameters.
    $active_trail = Drupal::service('menu.active_trail')->getActiveTrailIds($menu_name);
    $parameters = new MenuTreeParameters();
    $parameters->onlyEnabledLinks();
    $parameters->setMinDepth(1);
    $parameters->setMaxDepth(6);
    $parameters->setActiveTrail($active_trail);

    // Set the starting active menu level.
    $menu_trail_ids = array_reverse(array_values($parameters->activeTrail));
    if (isset($menu_trail_ids[$level - 1])) {
      $parameters->setRoot($menu_trail_ids[$level - 1]);
    }

    // Get the tree.
    $menu_tree_service = \Drupal::service('menu.link_tree');
    $tree = $menu_tree_service->load($menu_name, $parameters);

    // Apply some manipulators (checking the access, sorting).
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $menu_tree_service->transform($tree, $manipulators);

    // Actually build the tree.
    $menu_build_tree = $menu_tree_service->build($tree);

    // Create our storybook component friendly sidebar navigation.
    $variables['sidebar_navigation'] = [
      'menu_heading' => '',
      'links' => jcc_custom_build_menu_tree($menu_build_tree['#items']),
    ];
  }
}
