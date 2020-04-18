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
    case 'footer':
      $theme = \Drupal::service('theme.manager')->getActiveTheme()->getName();
      $social = [
        'fa-facebook-square' => theme_get_setting('facebook', $theme),
        'fa-twitter' => theme_get_setting('twitter', $theme),
        'fa-youtube' => theme_get_setting('youtube', $theme),
      ];

      foreach ($social as $icon => $uri) {
        $url = !empty($uri) ? Url::fromUri($uri) : NULL;
        $text = Markup::create('<i class="fab ' . $icon . '"></i>');
        $link = !empty($url) ? Link::fromTextAndUrl($text, $url) : NULL;
        $variables['social'][$icon] = $link;
      }
      $config = \Drupal::config('system.site');
      $variables['site']['name'] = $config->get('name');

      break;
  }
}
