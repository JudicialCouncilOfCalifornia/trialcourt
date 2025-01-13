<?php

/**
 * @file
 * Functions for page processing.
 */

use Drupal\node\NodeInterface;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_professional_preprocess_page(&$variables) {
  $variables['logo'] = theme_get_setting('logo', 'jcc_professional');
  $variables['header_footer_variant'] = theme_get_setting('header_footer_variant', 'jcc_professional');

  $variables['site_name'] = [
    'first' => theme_get_setting('site_name_first', 'jcc_professional'),
    'second' => theme_get_setting('site_name_second', 'jcc_professional'),
  ];

  $variables['main_links'] = jcc_components_get_menu_array('main');
  $variables['footer_links'] = jcc_components_get_menu_array('footer');
  $variables['social_links'] = jcc_professional_social_links();
  $variables['header_body'] = theme_get_setting('header_body');

  $variables['hat'] = [
    'title' => [
      'text' => theme_get_setting('hat_shoe_text', 'jcc_professional'),
      'url' => theme_get_setting('hat_shoe_url', 'jcc_professional'),
    ],
    'links' => jcc_components_get_menu_array('hat'),
  ];

  $variables['shoe'] = [
    'title' => [
      'text' => theme_get_setting('hat_shoe_text', 'jcc_professional'),
      'url' => theme_get_setting('hat_shoe_url', 'jcc_professional'),
    ],
    'links' => jcc_components_get_menu_array('shoe'),
  ];

  // Inherit root-level landing page masthead color.
  // Detects page & setting by path.
  $current_path = \Drupal::service('path.current')->getPath();
  $current_path_alias = \Drupal::service('path_alias.manager')->getAliasByPath($current_path);
  $current_path_alias = explode('/', $current_path_alias);
  // Root is also indexed so index must be greater than 2.
  if (count($current_path_alias) > 2) {
    $root_node_path = \Drupal::service('path_alias.manager')->getPathByAlias('/' . $current_path_alias[1]);
    $root_node_path = explode('/', $root_node_path);
    $root_node_id = end($root_node_path);
    $root_node = \Drupal::service('entity_type.manager')->getStorage('node')->load($root_node_id);
    if ($root_node && $root_node->hasField('field_masthead_color')) {
      $masthead_color = $root_node->get('field_masthead_color')->getString();
      // Never allow mega masthead for subpages.
      $variables['inherited_masthead_color'] = str_replace('mega ', '', $masthead_color);
    }
  }

  $node = \Drupal::routeMatch()->getParameter('node');
  if ($node instanceof NodeInterface) {
    if ($node->hasField('field_pre_selected_notification_')) {
      if (!$node->get('field_pre_selected_notification_')->isEmpty()) {
        if ($node->getType() == 'subpage' ||
          $node->getType() == 'landing_page') {
          $moduleHandler = \Drupal::service('module_handler');
          if ($moduleHandler->moduleExists('jcc_messaging_center')) {
            $groups = $node->field_pre_selected_notification_->referencedEntities();
            $variables['group_target_id'] = '';
            $group_labels = '';
            foreach ($groups as $key => $group) {
              if ($key == 0) {
                $group_labels = $group->label();
                $variables['group_target_id'] = $group->id();
              }
              else {
                $group_labels = $group_labels . ', ' . $group->label();
                $variables['group_target_id'] = $variables['group_target_id'] . '-' . $group->id();
              }
            }
          }
        }
      }
    }
  }
}

/**
 * Generate link array for social links.
 *
 * @return array
 *   An array of links formatted for a component.
 */
function jcc_professional_social_links() {
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
      'name' => 'instagram',
      'title' => t('Instagram'),
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
    $url = theme_get_setting($item['name'], 'jcc_professional');
    if (!empty($url)) {
      $social[$k]['url'] = $url;
    }
    else {
      unset($social[$k]);
    }
  }

  return $social;
}
