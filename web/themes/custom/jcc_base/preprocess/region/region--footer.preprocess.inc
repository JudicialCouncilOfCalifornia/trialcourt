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
