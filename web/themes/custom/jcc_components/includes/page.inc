<?php

/**
 * @file
 * Functions for page processing.
 */

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_page(&$variables) {
  $variables['logo'] = theme_get_setting('logo', 'jcc_components');
  $variables['header_footer_variant'] = theme_get_setting('header_footer_variant', 'jcc_components');

  $variables['site_name'] = [
    'first' => theme_get_setting('site_name_first', 'jcc_components'),
    'second' => theme_get_setting('site_name_second', 'jcc_components'),
  ];

  $variables['main_links'] = jcc_components_get_menu_array('main');
  $variables['footer_links'] = jcc_components_get_menu_array('footer');

  $variables['messaging_display_footer_form'] = \Drupal::config('jcc_messaging_center.settings')->get('messaging_display_footer_form');
  $variables['messaging_center_preselected_groups'] = '';
  if ($variables['messaging_display_footer_form'] == 1) {
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof NodeInterface) {
      if ($node->hasField('field_pre_selected_notification_')) {
        $preselected_values = $node->get('field_pre_selected_notification_')->getValue();
        foreach ($preselected_values as $key => $preselected_value) {
          if ($key == 0) {
            $variables['messaging_center_preselected_groups'] .= '?preselected=' . $preselected_value['target_id'];
          }
          else {
            $variables['messaging_center_preselected_groups'] .= ',' . $preselected_value['target_id'];
          }
        }
      }
    }
  }

  $variables['social_links'] = jcc_components_social_links();

  $variables['hat'] = [
    'title' => [
      'text' => theme_get_setting('hat_shoe_text', 'jcc_components'),
      'url' => theme_get_setting('hat_shoe_url', 'jcc_components'),
    ],
    'links' => jcc_components_get_menu_array('hat'),
  ];

  $variables['shoe'] = [
    'title' => [
      'text' => theme_get_setting('hat_shoe_text', 'jcc_components'),
      'url' => theme_get_setting('hat_shoe_url', 'jcc_components'),
    ],
    'links' => jcc_components_get_menu_array('shoe'),
  ];
  // Add text-only copyright to footer links.
  $variables['shoe']['links'][]['text'] = '© ' . date('Y');

  $path = \Drupal::service('path.current')->getPath();
  $langcodes = \Drupal::languageManager()->getLanguages();
  if (count($langcodes) > 1 && !theme_get_setting('hide_translation')) {
    $languages = [];
    $default = \Drupal::languageManager()->getDefaultLanguage()->getId();
    foreach ($langcodes as $langcode => $language) {
      $url = \Drupal::service('path_alias.manager')->getAliasByPath($path, $langcode);

      // If there's no alias use the path from the current route.
      if (empty($url)) {
        $url = Url::fromRoute('<current>')->toString();
      }

      // Add langcode prefix if not the default language.
      $node = \Drupal::routeMatch()->getParameter('node');
      // Add only if translated content exists.
      if ($node instanceof NodeInterface && $node->hasTranslation($langcode)) {
        $url = ($langcode == $default) ? $url : "/${langcode}${url}";
        $languages[] = [
          'langcode' => $langcode,
          'text' => $language->getName(),
          'url' => $url,
        ];
      }
    }
    // Display translate menu if not just default.
    if (count($languages) > 1) {
      $variables['translation_links'] = $languages;
    }
  }

  // Add Google Translate block.
  if (theme_get_setting('show_google_translate')) {
    $google_translate = \Drupal::service('plugin.manager.block')->createInstance('google_translate_block', []);
    if (isset($google_translate) && !empty($google_translate)) {
      $variables['google_translate'] = $google_translate->build();
    }
  }
  if (theme_get_setting('enable_twitter_embed') === 1) {
    $variables['#attached']['library'][] = 'jcc_components/twitter_widget';
  }

  // Display content only when embed param is 1.  Hide header and footer.
  $variables['embed'] = \Drupal::request()->query->get('embed') == 1 ? TRUE : FALSE;
}

/**
 * Generates menu array for component template.
 *
 * @param string $menu_name
 *   The name of the menu to generate.
 *
 * @return array
 *   The simple array to pass to component templates.
 */
function jcc_components_get_menu_array($menu_name) {
  $menu_tree = \Drupal::menuTree();
  $parameters = new MenuTreeParameters();
  $parameters->onlyEnabledLinks();
  $menu_active_trail = \Drupal::service('menu.active_trail')
    ->getActiveTrailIds($menu_name);
  $parameters->setActiveTrail($menu_active_trail);
  $tree = $menu_tree->load($menu_name, $parameters);
  $manipulators = [
    // Only show links that are accessible for the current user.
    ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
    ['callable' => 'menu.default_tree_manipulators:checkAccess'],
    // Use the default sorting of menu links.
    ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
  ];
  $tree = $menu_tree->transform($tree, $manipulators);
  return jcc_components_menu_to_array($tree, $menu_name);
}

/**
 * Recursive function to build menu link array.
 *
 * @param array $tree
 *   The tree of the menu to array-ify.
 * @param string $menu_name
 *   The name of the menu.
 *
 * @return array
 *   An array of links, formatted for a component.
 */
function jcc_components_menu_to_array(array $tree, $menu_name) {
  $links = [];

  foreach ($tree as $element) {
    // Don't include links the user doesnt' have access to.
    if (!$element->access->isAllowed()) {
      continue;
    }

    $section_link = [];
    if ($menu_name == 'main' && $element->depth = 1 && $element->hasChildren) {
      $section_link = [
        'text' => $element->link->getTitle(),
        'url' => $element->link->getUrlObject(),
      ];
    }
    $links[] = [
      'text' => $element->link->getTitle(),
      'url' => $element->link->getUrlObject(),
      'is_current' => $element->inActiveTrail,
      'links' => jcc_components_menu_to_array($element->subtree, $menu_name),
      'section_link' => $section_link,
    ];
  }
  return $links;
}

/**
 * Generate link array for social links.
 *
 * @return array
 *   An array of links formatted for a component.
 */
function jcc_components_social_links() {
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
      'name' => 'threads',
      'title' => t('Threads'),
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
    $url = theme_get_setting($item['name'], 'jcc_components');
    if (!empty($url)) {
      $social[$k]['url'] = $url;
    }
    else {
      unset($social[$k]);
    }
  }

  return $social;
}
