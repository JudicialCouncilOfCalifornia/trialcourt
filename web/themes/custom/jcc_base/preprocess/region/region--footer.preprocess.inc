<?php

/**
 * @file
 * Theme and preprocess functions for region.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_region__footer(&$variables) {
  $theme = \Drupal::service('theme.manager')->getActiveTheme()->getName();
  $site = \Drupal::config('system.site');

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

  $footer_menu = [];
  if (isset($footer_nav['#items']) && !empty($footer_nav['#items'])) {
    foreach ($footer_nav['#items'] as $key => $item) {
      $footer_menu[] = [
        'name' => $item['title'],
        'url' => $item['url'],
      ];
    }
  }

  $variables['footer_base'] = [
    'title' => 'Judicial Branch of California | ' . $site->get('name'),
    'links' => [],
    'social_links' => [],
    'global_bar' => [
      'logo_text' => [
        'title' => 'Judicial Branch of California',
        'url' => '/',
      ],
      'logo' => 'jcc-logo-branch-text-white.svg',
      'secondary_nav' => [
        'items' => $footer_menu,
      ],
    ],
  ];
}
