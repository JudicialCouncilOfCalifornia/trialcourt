<?php

use Drupal\Core\Menu\MenuTreeParameters;

/**
 * @file
 * Theme and preprocess functions for region.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_region__secondary_footer(&$variables) {
  $menu_tree = \Drupal::menuTree();
  $menu_name = "secondary-footer";
  $parameters = new MenuTreeParameters();
  $parameters->onlyEnabledLinks();
  $menu_active_trail = \Drupal::service('menu.active_trail')->getActiveTrailIds($menu_name);
  $parameters->setActiveTrail($menu_active_trail);
  $tree = $menu_tree->load($menu_name, $parameters);
  $manipulators = array(
    array('callable' => 'menu.default_tree_manipulators:checkAccess'),
    array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
  );
  $tree = $menu_tree->transform($tree, $manipulators);
  $footer_menu = $menu_tree->build($tree);

  $menu_link_tree_handler = Drupal::getContainer()->get('menu_item_extras.menu_link_tree_handler');
  $menu_link_tree_handler->processMenuLinkTree($footer_menu['#items'], $menu_name);

  $variables['list_section']['menu'] = [
    'classes' => [],
    'disable_header_link' => TRUE,
    'items' => $footer_menu['#items'],
  ];
}
