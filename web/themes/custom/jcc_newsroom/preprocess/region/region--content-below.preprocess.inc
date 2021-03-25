<?php

/**
 * @file
 * Theme and preprocess functions for region.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_newsroom_preprocess_region__content_below(&$variables) {
  $theme = \Drupal::service('theme.manager')->getActiveTheme()->getName();
  $site = \Drupal::config('system.site');

  // Set up arrays to pass to component.
  $variables['footer'] = [
    'site_name' => $site->get('name'),
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
            'name' => 'rss2',
            'title' => (string) t('RSS Feeds'),
            'decorative' => FALSE,
          ],
          'title' => (string) t('Feeds Directory'),
          'url' => theme_get_setting('rss', $theme),
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
      ],
    ],
    'links' => [
      'patternlab' => TRUE,
      'inline_nav' => [
        'items' => [],
      ],
    ],
  ];

}
