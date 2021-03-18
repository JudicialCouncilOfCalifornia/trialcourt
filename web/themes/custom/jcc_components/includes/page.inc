<?php

/**
 * @file
 * Functions for page processing.
 */

use Drupal\Core\Menu\MenuTreeParameters;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_page(&$variables) {
  // Get the footer menu.
  $variables['header_footer_variant'] = theme_get_setting('header_footer_variant', 'jcc_components');
  $variables['site_name'] = [
    'first' => theme_get_setting('site_name_first', 'jcc_components'),
    'second' => theme_get_setting('site_name_second', 'jcc_components'),
  ];
  $variables['footer_links'] = jcc_components_get_menu_array('footer');
  $variables['social_links'] = jcc_components_social_links();
  $variables['shoe'] = [
    'title' => [
      'text' => theme_get_setting('hat_shoe_text', 'jcc_components'),
      'url' => theme_get_setting('hat_shoe_url', 'jcc_components'),
    ],
    'links' => jcc_components_get_menu_array('shoe'),
  ];
}

/**
 * Generates menu array for component template.
 *
 * @param string $menu_name
 *   The name of the menu to generate.
 *
 * @return array
 *   The simple array to pass to component templates.
 */
function jcc_components_get_menu_array($menu_name) {
  $menu_tree = \Drupal::menuTree();
  $parameters = new MenuTreeParameters();
  $parameters->onlyEnabledLinks();
  $menu_active_trail = \Drupal::service('menu.active_trail')
    ->getActiveTrailIds($menu_name);
  $parameters->setActiveTrail($menu_active_trail);
  $tree = $menu_tree->load($menu_name, $parameters);
  $manipulators = [
    // Only show links that are accessible for the current user.
    ['callable' => 'menu.default_tree_manipulators:checkAccess'],
    // Use the default sorting of menu links.
    ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
  ];
  $tree = $menu_tree->transform($tree, $manipulators);
  return jcc_components_menu_to_array($tree);
}

/**
 * Recursive function to build menu link array.
 *
 * @param array $tree
 *   The tree of the menu to array-ify.
 *
 * @return array
 *   An array of links, formatted for a component.
 */
function jcc_components_menu_to_array(array $tree) {
  foreach ($tree as $element) {
    $links[] = [
      'text' => $element->link->getTitle(),
      'url' => $element->link->getUrlObject(),
      'links' => jcc_components_menu_to_array($element->subtree),
    ];
  }
  return $links;
}

/**
 * Generate link array for social links.
 *
 * @return array
 *   An array of links formatted for a component.
 */
function jcc_components_social_links() {
  $social = [
    [
      'name' => 'email',
      'title' => t('Email'),
    ],
    [
      'name' => 'facebook',
      'title' => t('Facebook'),
    ],
    [
      'name' => 'flickr',
      'title' => t('Flickr'),
    ],
    [
      'name' => 'linkedin',
      'title' => t('LinkedIn'),
    ],
    [
      'name' => 'rss',
      'title' => t('RSS'),
    ],
    [
      'name' => 'twitter',
      'title' => t('Twitter'),
    ],
    [
      'name' => 'youtube',
      'title' => t('YouTube'),
    ],
  ];

  foreach ($social as $k => $item) {
    $url = theme_get_setting($item['name'], 'jcc_components');
    if (!empty($url)) {
      $social[$k]['url'] = $url;
    }
    else {
      unset($social[$k]);
    }
  }

  return $social;
}
