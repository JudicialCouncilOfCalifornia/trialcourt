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
  // BEGIN Get basic site settings (e.g. site name for logo alt)
  $config = \Drupal::config('system.site');
  $site_name = $config->get('name');
  // END.
  $variables['header'] = [
    'mobile' => [
      'globe' => [
        'name' => 'globe',
        'title' => (string) t('Translate'),
        'decorative' => TRUE,
      ],
      'search' => [
        'name' => 'search',
        'title' => (string) t('Search'),
        'decorative' => TRUE,
      ],
      'logo' => [
        'src' => $theme_logo,
        'alt' => $site_name . ' logo',
        'renderer' => 'patternlab',
      ],
    ],
    'header_topbar' => [
      'logo' => [
        'src' => $theme_logo,
        'alt' => $site_name . ' logo',
        'renderer' => 'patternlab',
      ],
      'search' => [
        'form_action' => '/search',
        'search_input' => [
          'form_input_name' => 'keywords',
          'placeholder' => \Drupal::request()->query->get('keywords') ?: 'Search',
        ],
      ],
      'dropbutton' => [
        'icon' => [
          'name' => "globe-2",
          'title' => "Translate",
          'decorative' => TRUE,
        ],
        'label' => "Translate",
      ],
      'hide_translation' => $hide_translation,
    ],
  ];

  $variables['header']['header_topbar']['dropbutton']['links'] = jcc_base_translation_menu();

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
    $variables['header']['primary_nav']['lowercase_titles'] = TRUE;
    $variables['header']['primary_nav']['items'] = jcc_base_mainnavigation_build($main_nav['#items'], $menu_link_tree_handler);

    $moduleHandler = Drupal::service('module_handler');
    $user = Drupal::currentUser();
    if ($moduleHandler->moduleExists('contextual') && $user->hasPermission('access contextual links')) {
      $contextual_links = [
        'menu' => [
          'route_parameters' => [
            'menu' => $menu_name,
          ],
        ],
      ];
      $variables['header']['primary_nav']['contextual_links'] = [
        '#type' => 'contextual_links_placeholder',
        '#id' => _contextual_links_to_id($contextual_links),
      ];
    }
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
      'name' => $original_item['title'],
      'url' => $original_item['url'],
    ];
    $item['description'] = $entity->field_text && !$entity->field_text->isEmpty() ? $entity->field_text->first()->getValue()['value'] : NULL;
    $item['icon'] = $entity->field_fa_icon && !$entity->field_fa_icon->isEmpty() ? ['#markup' => $entity->field_fa_icon->first()->getValue()['value']] : NULL;

    if (!empty($original_item['below'])) {
      $item['sublinks'] = jcc_base_mainnavigation_build($original_item['below'], $menu_link_tree_handler);
    }
    $items[] = $item;
  }
  return $items;
}
