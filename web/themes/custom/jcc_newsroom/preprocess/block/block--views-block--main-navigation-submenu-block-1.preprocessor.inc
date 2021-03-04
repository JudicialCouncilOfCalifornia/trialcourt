<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_block__views_block__main_navigation_submenu_block_1(&$variables) {
  $menu_name = 'main';
  $menu_tree = Drupal::menuTree();
  $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
  $parameters->setMinDepth(2);
  $submenu = $menu_tree->load($menu_name, $parameters);
  $manipulators = [
    ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
  ];
  $submenu = $menu_tree->transform($submenu, $manipulators);

  $links = [];
  foreach ($submenu as $item) {
    if ($item->inActiveTrail == TRUE && $item->hasChildren == TRUE) {
      foreach ($item->subtree as $subitem) {
        $link_title = $subitem->link->getTitle();
        $links[] = [
          'title' => $link_title,
          'action' => [
            'style' => 'card',
            'url' => $subitem->link->getUrlObject()->toString(),
          ],
        ];
      }
    }
  }

  $variables['card_section'] = [
    'theme' => 'jcc-card-section--newsroom',
    'headergroup' => [
      'layout' => [
        'spacing' => 'padding-top-4',
        'alignment' => 'center',
      ],
      'tag' => 'h1',
      'title' => $variables['elements']['#configuration']['label'],
    ],
    'cards' => $links,
    'background' => 'dark',
  ];
}
