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

  // Get links from footer menu directly.
  $menu_name = 'footer';
  $menu_tree = \Drupal::menuTree();
  $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
  $parameters->setMinDepth(0)->onlyEnabledLinks();

  $tree = $menu_tree->load($menu_name, $parameters);
  $manipulators = array(
    array('callable' => 'menu.default_tree_manipulators:checkAccess'),
    array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
  );
  $tree = $menu_tree->transform($tree, $manipulators);
  $footer_nav = $menu_tree->build($tree);

  if (isset($footer_nav['#items']) && !empty($footer_nav['#items'])) {
    foreach ($footer_nav['#items'] as $key => $item) {
      $variables['footer_tall']['links']['inline_nav']['items'][] = [
        'name' => $item['title'],
        'url' => $item['url']
      ];
    }
    $moduleHandler = Drupal::service('module_handler');
    $user = Drupal::currentUser();
    if ($moduleHandler->moduleExists('contextual') && $user->hasPermission('access contextual links')) {
      $contextual_links = [
        'menu' => [
          'route_parameters' => [
            'menu' => $menu_name
          ]
        ]
      ];
      $variables['footer_tall']['links']['inline_nav']['contextual_links'] = [
        '#type' => 'contextual_links_placeholder',
        '#id' => _contextual_links_to_id($contextual_links),
      ];
    }
  }
}
