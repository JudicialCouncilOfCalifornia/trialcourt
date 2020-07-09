<?php

/**
 * @file
 * Preprocess Region Secondary Footer.
 */

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
  $manipulators = [
    ['callable' => 'menu.default_tree_manipulators:checkAccess'],
    ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
  ];
  $tree = $menu_tree->transform($tree, $manipulators);
  $footer_menu = $menu_tree->build($tree);

  if (!empty($footer_menu['#items'])) {
    $menu_link_tree_handler = Drupal::getContainer()->get('menu_item_extras.menu_link_tree_handler');
    $menu_link_tree_handler->processMenuLinkTree($footer_menu['#items'], $menu_name);

    $variables['list_section']['lowercase_titles'] = TRUE;

    $variables['list_section']['menu'] = [
      'classes' => [],
      'lowercase_titles' => TRUE,
      'disable_header_link' => TRUE,
      'items' => $footer_menu['#items'],
    ];

    $moduleHandler = Drupal::service('module_handler');
    $user = Drupal::currentUser();
    $placeholder = FALSE;
    if ($moduleHandler->moduleExists('contextual') && $user->hasPermission('access contextual links')) {
      $contextual_links = [
        'menu' => [
          'route_parameters' => [
            'menu' => $menu_name,
          ],
        ],
      ];
      $placeholder = [
        '#type' => 'contextual_links_placeholder',
        '#id' => _contextual_links_to_id($contextual_links),
      ];
    }
    $variables['list_section']['menu']['contextual_links'] = $placeholder;
  }
}
