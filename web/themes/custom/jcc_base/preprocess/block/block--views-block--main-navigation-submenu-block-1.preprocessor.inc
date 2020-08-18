<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_block__views_block__main_navigation_submenu_block_1(&$variables) {
  $buttons = [];
  $menu_name = 'main';
  $menu_tree = \Drupal::menuTree();
  $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
  $parameters->setMinDepth(2);
  $parameters->onlyEnabledLinks();
  $tree = $menu_tree->load($menu_name, $parameters);
  $manipulators = [
    ['callable' => 'menu.default_tree_manipulators:checkAccess'],
    ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
  ];
  $tree = $menu_tree->transform($tree, $manipulators);

  foreach ($tree as $item) {
    if ($item->inActiveTrail && $item->hasChildren) {
      foreach ($item->subtree as $child) {
        $submenu_title = $child->link->getTitle();
        $submenu_url = $child->link->getUrlObject();

        $submenu = [
          'title' => $submenu_title,
          'url' => $submenu_url,
        ];

        array_push($buttons, $submenu);
      }
    }
  }

  $variables['submenu_buttons'] = $buttons;
}
