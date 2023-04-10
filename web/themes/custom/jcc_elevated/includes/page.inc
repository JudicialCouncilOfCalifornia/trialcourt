<?php

/**
 * @file
 * Page preprocessors.
 */

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_elevated_preprocess_page(&$variables) {
  $variables['logo'] = theme_get_setting('logo', 'jcc_elevated');

  $variables['site_name'] = [
    'first' => theme_get_setting('site_name_first', 'jcc_elevated'),
    'second' => theme_get_setting('site_name_second', 'jcc_elevated'),
  ];

  $variables['main_links'] = jcc_elevated_get_menu_array('main');
  $variables['footer_links'] = jcc_elevated_get_menu_array('footer');

  $variables['hat'] = [
    'title' => [
      'text' => theme_get_setting('hat_shoe_text', 'jcc_elevated'),
      'url' => theme_get_setting('hat_shoe_url', 'jcc_elevated'),
    ],
    'links' => jcc_elevated_get_menu_array('hat'),
  ];

  $variables['shoe'] = [
    'title' => [
      'text' => theme_get_setting('hat_shoe_text', 'jcc_elevated'),
      'url' => theme_get_setting('hat_shoe_url', 'jcc_elevated'),
    ],
    'links' => jcc_elevated_get_menu_array('shoe'),
  ];

  $path = \Drupal::service('path.current')->getPath();
  $langcodes = \Drupal::languageManager()->getLanguages();

  if (count($langcodes) > 1 && !theme_get_setting('hide_translation')) {
    foreach ($langcodes as $langcode => $language) {
      $default = \Drupal::languageManager()->getDefaultLanguage()->getId();
      $url = \Drupal::service('path_alias.manager')->getAliasByPath($path, $langcode);

      // If there's no alias use the path from the current route.
      if (empty($url)) {
        $url = Url::fromRoute('<current>')->toString();
      }

      // Add langcode prefix if not the default language.
      $url = ($langcode == $default) ? $url : "/{$langcode}{$url}";

      $variables['translation_links'][] = [
        'text' => $language->getName(),
        'url' => $url,
      ];
    }
  }

  // Add Google Translate block.
  if (theme_get_setting('show_google_translate')) {
    $google_translate = \Drupal::service('plugin.manager.block')->createInstance('google_translate_block', []);
    if (isset($google_translate) && !empty($google_translate)) {
      $variables['google_translate'] = $google_translate->build();
    }
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
function jcc_elevated_get_menu_array($menu_name) {
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
  return jcc_elevated_menu_to_array($tree, $menu_name);
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
function jcc_elevated_menu_to_array(array $tree, $menu_name) {
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
      'links' => jcc_elevated_menu_to_array($element->subtree, $menu_name),
      'section_link' => $section_link,
    ];
  }
  return $links;
}
