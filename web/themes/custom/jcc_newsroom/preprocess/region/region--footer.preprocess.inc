<?php

/**
 * @file
 * Theme and preprocess functions for region.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_newsroom_preprocess_region__footer(&$variables) {
  $site = \Drupal::config('system.site');

  // Set up arrays to pass to component.
  $variables['footer'] = [
    'layout_variant' => 'minimal',
    'copyright_owner' => $site->get('name'),
    'links' => [
      'patternlab' => TRUE,
      'inline_nav' => [
        'items' => [],
      ],
    ],
  ];

  // Get links from footer menu directly.
  $menu_name = 'footer';
  $menu_tree = \Drupal::menuTree();
  $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
  $parameters->setMinDepth(0)->onlyEnabledLinks();

  $tree = $menu_tree->load($menu_name, $parameters);
  $manipulators = [
    ['callable' => 'menu.default_tree_manipulators:checkAccess'],
    ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
  ];
  $tree = $menu_tree->transform($tree, $manipulators);
  $footer_nav = $menu_tree->build($tree);

  if (isset($footer_nav['#items']) && !empty($footer_nav['#items'])) {
    foreach ($footer_nav['#items'] as $key => $item) {
      $variables['footer']['links']['inline_nav']['items'][] = [
        'name' => $item['title'],
        'url' => $item['url'],
      ];
    }
  }
}
