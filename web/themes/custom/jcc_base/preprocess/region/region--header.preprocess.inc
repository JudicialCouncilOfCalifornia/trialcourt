<?php

/**
 * @file
 * Theme and preprocess functions for region.
 */

use Drupal\menu_item_extras\Service\MenuLinkTreeHandler;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_region__header(&$variables) {
  // Get theme logo from appearance setting.
  $theme_logo = theme_get_setting('logo.url');
  // Get translation hiding settings.
  $hide_translation = theme_get_setting('hide_translation');
  // Get theme variations.
  $header_extended = theme_get_setting('header_extended');
  $mega_menu = theme_get_setting('mega_menu');
  $inverted_color = theme_get_setting('color_inverted');

  // BEGIN Get basic site settings (e.g. site name for logo alt)
  $config = \Drupal::config('system.site');
  $site_name = $config->get('name');
  // END.
  $translation_links = jcc_base_translation_menu();

  $menu_name = 'main';
  $menu_tree = \Drupal::menuTree();
  $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
  $parameters->setMinDepth(0);
  $parameters->onlyEnabledLinks();

  $tree = $menu_tree->load($menu_name, $parameters);
  $manipulators = [
    ['callable' => 'menu.default_tree_manipulators:checkAccess'],
    ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
  ];
  $tree = $menu_tree->transform($tree, $manipulators);
  $main_nav = $menu_tree->build($tree);

  if (!empty($main_nav['#items'])) {
    $menu_link_tree_handler = Drupal::getContainer()->get('menu_item_extras.menu_link_tree_handler');
    $primary_nav['links'] = jcc_base_mainnavigation_build($main_nav['#items'], $menu_link_tree_handler);
    if ($header_extended) {
      $header_height_variant = "extended";
      if ($mega_menu) {
        $primary_nav['mega'] = TRUE;
      }
      else {
        $primary_nav['mega'] = FALSE;
      }
    }
    else {
      $header_height_variant = "basic";
    }
  }

  if ($inverted_color) {
    $background_variant = 'inverted';
    $logo = FALSE;
  }
  else {
    $background_variant = FALSE;
    $logo['desktop'] = $theme_logo;
    $logo['mobile'] = $theme_logo;
  }

  $variables['extended_header'] = TRUE;
  $variables['header'] = [
    'height_variant' => $header_height_variant,
    'background_variant' => $background_variant,
    'global_bar' => [
      'logo_text' => [
        'title' => 'Judicial Branch of California',
        'url' => 'https://www.courts.ca.gov',
      ],
      'logo' => '../../images/logos/jcc-logo-branch-text-white.svg',
      'secondary_nav' => [
        'items' => [
          [
            'name' => 'Supreme Court',
            'url' => 'https://www.courts.ca.gov/supremecourt.htm',
          ], [
            'name' => 'Courts of Appeal',
            'url' => 'https://www.courts.ca.gov/courtsofappeal.htm',
          ], [
            'name' => 'Superior Courts',
            'url' => 'https://www.courts.ca.gov/superiorcourts.htm',
          ], [
            'name' => 'Judicial Council',
            'url' => 'https://www.courts.ca.gov/policyadmin-jc.htm',
          ],
        ],
      ],
    ],
    'primary_nav' => $primary_nav,
    'title' => $site_name,
    'logo' => $logo,
    'home_url' => '/',
    'menu_icon' => [
      'name' => 'menu',
      'title' => "Main navigation",
      'decorative' => FALSE,
    ],
    'close_icon' => [
      'name' => 'close',
      'title' => 'Close main navigation',
      'decorative' => FALSE,
    ],
    'nav_secondary' => [
      'search' => [
        'form_action' => "/search",
        'search_input' => [
          'form_input_name' => "keywords",
        ],
      ],
      'accordion_button' => [
        'id' => 'extended-nav-section-two',
        'icon' => [
          'name' => "translate",
          'title' => "Translate",
          'decorative' => TRUE,
        ],
        'label' => "Translate",
        'links' => $translation_links,
      ],
    ],
  ];
  if ($hide_translation) {
    $variables['header']['nav_secondary']['accordion_button'] = [];
  }
}

/**
 * Format translation links for drobputton component.
 *
 * @todo : Return actual enabled languages. This will be affected by:
 *   - enabled languages
 *   - filtered by languages enabled for specific courts
 *   - depending if this is multisite or domain access.
 *
 * @return array
 *   Array of link items for dropbutton component.
 */
function jcc_base_translation_menu() {

  $host = \Drupal::request()->getSchemeAndHttpHost();
  $language_manager = \Drupal::languageManager();
  $languages = $language_manager->getLanguages();
  $default_language = $language_manager->getDefaultLanguage();
  $current_path = \Drupal::request()->getRequestUri();

  // Strip language from current path.
  foreach ($languages as $langcode => $language) {
    $current_path = strpos($current_path, "/$langcode/") === 0
    || $current_path == "/$langcode"
      ? str_replace("/$langcode", '', $current_path)
      : $current_path;
  }
  // Create links for dropbutton component.
  foreach ($languages as $langcode => $language) {
    // Default language paths have no prefix.
    $langcode = $langcode == $default_language->getId() ? '' : "/$langcode";

    $links[] = [
      'title' => $language->getName(),
      'href' => "$host$langcode$current_path",
    ];
  }

  return $links;
}

/**
 * Recursive function to map menu to component array format.
 *
 * @param array $original_items
 *   The top level links for the menu.
 * @param Drupal\menu_item_extras\Service\MenuLinkTreeHandler $menu_link_tree_handler
 *   Service for loading additional link field data.
 *
 * @return array
 *   Array formetted for assocaited pattern lab components.
 */
function jcc_base_mainnavigation_build(array $original_items, MenuLinkTreeHandler $menu_link_tree_handler) {
  foreach ($original_items as $original_item) {
    $entity = $menu_link_tree_handler->getMenuLinkItemEntity($original_item['original_link']);
    $item = [
      'text' => $original_item['title'],
      'is_current' => $original_item['in_active_trail'],
    ];
    if (!empty($original_item['below'])) {
      $item['id'] = 'menu-item-' . $entity->id();
      $item['is_current'] = $original_item['in_active_trail'];
      $item['links'] = jcc_base_mainnavigation_build($original_item['below'], $menu_link_tree_handler);
      if ($original_item['url']->isRouted()) {
        if ($original_item['url']->getRouteName() != '<nolink>') {
          $item['href'] = $original_item['url'];
        }
      }
      else {
        $item['href'] = $original_item['url'];
      }
    }
    else {
      $item['href'] = $original_item['url'];
    }
    $items[] = $item;
  }
  return $items;
}
