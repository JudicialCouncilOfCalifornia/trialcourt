<?php

/**
 * @file
 * Theme and preprocess functions for region.
 */

use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

/**
 * Implements hook_preproess_HOOK().
 */
function jcc_base_preprocess_region(&$variables) {
  switch ($variables['elements']['#region']) {

    case 'header':
      jcc_base_header_region($variables);
      break;

    case 'secondary_footer':
      jcc_base_secondary_footer_region($variables);
      break;

    case 'footer':
      jcc_base_footer_region($variables);
      break;

  }
}

/**
 * Helper function for secondary header preprocess.
 */
function jcc_base_header_region(&$variables) {
  $pl_path = base_path() . 'libraries/courtyard-artifact';
  $main_nav = $variables['elements']['mainnavigation']['content'];
  $variables['header'] = [
    'mobile' => [
      'globe' => [
        'name' => 'globe',
        'title' => (string) t('Translate'),
        'decorative' => TRUE
      ],
      'search' => [
        'name' => 'search',
        'title' => (string) t('Search'),
        'decorative' => TRUE
      ],
      'logo' => [
        'src' => $pl_path . '/public/images/jcc-logo-long.png',
        'alt' => (string) t('Judicial Court Logo'),
        'renderer' => 'patternlab'
      ]
    ],
    'header_topbar' => [
      'logo' => [
        'src' => $pl_path . '/public/images/jcc-logo-long.png',
        'alt' => (string) t('Judicial Court Logo'),
        'renderer' => 'patternlab'
      ],
      'dropbutton' => [
        'icon' => [
          'name' => "globe-2",
          'title' => "Translate",
          'decorative' => TRUE
        ],
        'label' => "Translate",
      ]
    ]
  ];

  $variables['header']['header_topbar']['dropbutton']['links'] = jcc_base_translation_menu();

  $menu_link_tree_handler = Drupal::getContainer()->get('menu_item_extras.menu_link_tree_handler');
  $variables['header']['primary_nav']['items'] = jcc_base_mainnavigation_build($main_nav['#items'], $menu_link_tree_handler);
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
      'href' => "$host$langcode$current_path"
    ];
  }

  return $links;
}

/**
 * Recursive function to map menu to component array format.
 *
 * @param  array $original_items
 *   The top level links for the menu.
 * @param  MenuLinkTreeHandler $menu_link_tree_handler
 *   Service for loading additional link field data.
 *
 * @return array
 *   Array formetted for assocaited pattern lab components.
 */
function jcc_base_mainnavigation_build($original_items, $menu_link_tree_handler) {
  foreach($original_items as $original_item) {
    $entity = $menu_link_tree_handler->getMenuLinkItemEntity($original_item['original_link']);
    $item = [
      'name' => $original_item['title'],
      'url' => $original_item['url'],
    ];
    $item['description'] = !$entity->field_text->isEmpty() ? $entity->field_text->first()->getValue()['value'] : NULL;

    if (!empty($original_item['below'])) {
      $item['sublinks'] = jcc_base_mainnavigation_build($original_item['below'], $menu_link_tree_handler);
    }
    $items[] = $item;
  }
  return $items;
}

/**
 * Helper function for secondary footer preprocess.
 */
function jcc_base_secondary_footer_region(&$variables) {
  $footer_menu = $variables['elements']['secondaryfooter']['content'];

  $variables['list_section']['menu'] = [
    'classes' => [],
    'disable_header_link' => TRUE,
    'items' => $footer_menu['#items']
  ];
}

/**
 * Helper function for footer preprocess.
 */
function jcc_base_footer_region(&$variables) {
  $theme = \Drupal::service('theme.manager')->getActiveTheme()->getName();
  $site = \Drupal::config('system.site');
  $content = $variables['elements']['footer']['content'];
  // Set up arrays to pass to component.
  $variables['footer_tall'] = [
    'site_name' => $site->get('name'),
    'social_links' => [
      'items' => [
        [
          'icon' => [
            'name' => 'youtube',
            'title' => (string) t('YouTube'),
            'decorative' => FALSE
          ],
          'title' => (string) t('YouTube'),
          'url' => theme_get_setting('youtube', $theme)
        ],
        [
          'icon' => [
            'name' => 'facebook2',
            'title' => (string) t('Facebook'),
            'decorative' => FALSE
          ],
          'title' => (string) t('Facebook'),
          'url' => theme_get_setting('facebook', $theme)
        ],
        [
          'icon' => [
            'name' => 'twitter',
            'title' => (string) t('Twitter'),
            'decorative' => FALSE
          ],
          'title' => (string) t('Twitter'),
          'url' => theme_get_setting('twitter', $theme)
        ]
      ]
    ],
    'links' => [
      'patternlab' => TRUE,
      'inline_nav' => [
        'items' => []
      ]
    ]
  ];

  foreach ($content['#items'] as $key => $item) {
    $variables['footer_tall']['links']['inline_nav']['items'][] = [
      'name' => $item['title'],
      'url' => $item['url']
    ];
  }
}
