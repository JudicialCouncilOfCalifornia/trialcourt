<?php

/**
 * @file
 * Functions for page processing.
 */

use Drupal\node\Entity\Node;

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
  $variables['social_links'] = jcc_components_social_links();
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

  if ($variables['node']) {
    // Inherit masthead color setting if subpage.
    $breadcrumb = \Drupal::service('breadcrumb')->build(\Drupal::routeMatch());
    $root_id = basename($breadcrumb->getLinks()[0]->getUrl()->getInternalPath());
    if ($root_id) {
      $parent_masthead_color = Node::load($root_id)->field_masthead_color->getString();
      // Never allow mega masthead for subpages.
      $variables['parent_masthead_color'] = str_replace('mega ', '', $parent_masthead_color);
    }
  }
}
