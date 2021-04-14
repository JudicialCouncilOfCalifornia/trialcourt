<?php

/**
 * @file
 * Theme and preprocess functions for region.
 */

use Drupal\system\Entity\Menu;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_region__footer(&$variables) {
  $theme = \Drupal::service('theme.manager')->getActiveTheme()->getName();
  $site = \Drupal::config('system.site');
  $variables['site_entity_type'] = theme_get_setting('site_entity_type');
  $variables['footer_extended'] = theme_get_setting('footer_extended');
  $variables['footer_extended_message'] = theme_get_setting('footer_extended_message');

  // Site branding.
  $site_name = $site->get('name');
  if (!empty($variables['footer_extended']) && $variables['footer_extended_message']) {
    $site_name = $variables['footer_extended_message'];
  }
  switch ($variables['site_entity_type']) {
    case 'superior':
      $site_title = 'Superior Court of California | ' . $site_name;
      break;

    default:
      $site_title = $site_name;
  }

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

  // Get secondary footer menu.
  $sec_footer_menu_name = 'secondary-footer';
  $menu = Menu::load($sec_footer_menu_name);
  $footer_aria_label = $menu->getThirdPartySetting('sitemap', 'sitemap_display_name');
  $sec_footer_menu_parameters = $menu_tree->getCurrentRouteMenuTreeParameters($sec_footer_menu_name);
  $sec_footer_menu_parameters->setMinDepth(0);
  $sec_footer_menu_parameters->onlyEnabledLinks();

  $sec_footer_menu_tree = $menu_tree->load($sec_footer_menu_name, $sec_footer_menu_parameters);
  $sec_footer_menu_tree = $menu_tree->transform($sec_footer_menu_tree, $manipulators);
  $main_nav = $menu_tree->build($sec_footer_menu_tree);

  $footer_menu = [];
  if (isset($footer_nav['#items']) && !empty($footer_nav['#items'])) {
    foreach ($footer_nav['#items'] as $item) {
      $footer_menu[] = [
        'name' => $item['title'],
        'url' => $item['url'],
      ];
    }
  }

  $footer_menu_primary = [];
  $secondary_menu_cols = '3';
  if ($variables['footer_extended']) {
    if (!empty($main_nav['#items'])) {
      $menu_link_tree_handler = Drupal::getContainer()->get('menu_item_extras.menu_link_tree_handler');
      $footer_menu_primary = jcc_base_mainnavigation_build($main_nav['#items'], $menu_link_tree_handler);
      if (count($footer_menu_primary) == 2) {
        $secondary_menu_cols = '6';
      }
    }
  }

  $variables['footer'] = [
    'aria_label' => $footer_aria_label,
    'layout_variant' => 'minimal',
    'copyright_owner' => $site->get('name'),
    'links' => [
      'patternlab' => TRUE,
      'inline_nav' => [
        'items' => [],
      ],
    ],
  ];

  $variables['footer_base'] = [
    'aria_label' => $footer_aria_label,
    'height_variant' => 'big',
    'title' => $site_title,
    'links' => $footer_menu_primary,
    'secondary_menu_cols' => $secondary_menu_cols,
    'social_links' => [
      'items' => [
        [
          'icon' => [
            'name' => 'youtube',
            'title' => (string) t('YouTube'),
            'decorative' => FALSE,
          ],
          'title' => (string) t('YouTube'),
          'url' => theme_get_setting('youtube', $theme),
        ],
        [
          'icon' => [
            'name' => 'twitter',
            'title' => (string) t('Twitter'),
            'decorative' => FALSE,
          ],
          'title' => (string) t('Twitter'),
          'url' => theme_get_setting('twitter', $theme),
        ],
        [
          'icon' => [
            'name' => 'flickr',
            'title' => (string) t('Flickr'),
            'decorative' => FALSE,
          ],
          'title' => (string) t('Flickr'),
          'url' => theme_get_setting('flickr', $theme),
        ],
        [
          'icon' => [
            'name' => 'linkedin2',
            'title' => (string) t('LinkedIn'),
            'decorative' => FALSE,
          ],
          'title' => (string) t('LinkedIn'),
          'url' => theme_get_setting('linkedin', $theme),
        ],
        [
          'icon' => [
            'name' => 'rss2',
            'title' => (string) t('RSS'),
            'decorative' => FALSE,
          ],
          'title' => (string) t('RSS'),
          'url' => theme_get_setting('rss', $theme),
        ],
        [
          'icon' => [
            'name' => 'email',
            'title' => (string) t('Email'),
            'decorative' => FALSE,
          ],
          'title' => (string) t('Email'),
          'url' => theme_get_setting('email', $theme),
        ],
      ],
    ],
    'global_bar' => [
      'logo_text' => [
        'title' => 'Judicial Branch of California',
        'url' => 'https://www.courts.ca.gov',
      ],
      'logo' => '../../images/logos/jcc-logo-branch-text-white.svg',
      'secondary_nav' => [
        'items' => $footer_menu,
      ],
    ],
  ];
}
