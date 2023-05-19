<?php

/**
 * @file
 * Page preprocessors.
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Form\FormState;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\views\Views;

/**
 * Implements hook_preprocess_HOOK().
 *
 * @throws \Drupal\Core\Form\FormAjaxException
 */
function jcc_elevated_preprocess_page(&$variables) {
  // Check if front page.
  $is_front = \Drupal::service('path.matcher')->isFrontPage();

  // Determine if page is a node.
  $is_node = FALSE;
  $node = \Drupal::routeMatch()->getParameter('node');
  if ($node instanceof NodeInterface) {
    $is_node = TRUE;
  }

  $variables['logo'] = theme_get_setting('logo', 'jcc_elevated');

  $variables['site_name'] = [
    'first' => theme_get_setting('site_name_first', 'jcc_elevated'),
    'second' => theme_get_setting('site_name_second', 'jcc_elevated'),
  ];

  $variables['main_links'] = jcc_elevated_get_menu_array('main');
  $variables['featured_links'] = jcc_elevated_get_menu_array('featured-links');
  $variables['footer_links'] = jcc_elevated_get_menu_array('footer');

  $variables['search_form'] = jcc_elevated_build_header_search_form();
  $variables['search_form_mobile'] = jcc_elevated_build_header_search_form(TRUE);

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
  $variables['embed'] = \Drupal::request()->query->get('embed') == 1;

  // Set breadcrumb for landing pages and subpages.
  $variables['breadcrumb'] = FALSE;
  $types = ['landing_page', 'subpage'];
  if (!$is_front && $is_node && in_array($node->bundle(), $types)) {
    $variables['breadcrumb'] = \Drupal::service('breadcrumb')
      ->build(\Drupal::routeMatch())
      ->toRenderable();
  }
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
function jcc_elevated_get_menu_array(string $menu_name): array {
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
function jcc_elevated_menu_to_array(array $tree, string $menu_name): array {
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

/**
 * Generate the search form from the search view exposed form.
 *
 * @param bool $mobile_version
 *   Sets different id's and any other tweaks needed for accessibility on the
 *   duplicate search forms.
 *
 * @return array
 *   Return a renderable array.
 *
 * @throws \Drupal\Core\Form\FormAjaxException
 */
function jcc_elevated_build_header_search_form(bool $mobile_version = FALSE): array {
  $view_name = 'search';
  $display_name = 'search';
  $searched_value = \Drupal::request()->query->get('search');
  $form = [];

  $view = Views::getView($view_name);
  $view->setDisplay($display_name);
  $view->initHandlers();
  $form_state = (new FormState())
    ->setStorage([
      'view' => $view,
      'display' => &$view->display_handler->display,
      'rerender' => TRUE,
    ])
    ->setMethod('get')
    ->setAlwaysProcess()
    ->disableRedirect();
  $form_state->set('rerender', NULL);
  try {
    $form = \Drupal::formBuilder()
      ->buildForm('\Drupal\views\Form\ViewsExposedForm', $form_state);
  }
  catch (EnforcedResponseException $e) {
  }

  $form['#view_name'] = $view_name;
  $form['#display_name'] = $display_name;
  array_unshift($form['#theme'], 'views_exposed_form__search__search__header_form');
  if ($mobile_version) {
    array_unshift($form['#theme'], 'views_exposed_form__search__search__header_form__mobile');
  }

  foreach (Element::children($form['actions']) as $action_key) {
    $form['actions'][$action_key]['#attributes']['data-views-ajax-submit-disabled'] = TRUE;
  }

  // Class necessary to trigger storybook styling.
  $form['#attributes']['class'][] = 'header-search__form';

  // Alter the search field.
  $form['search']['#type'] = 'textfield';
  $form['search']['#pre_render'] = [];
  $form['search']['#process'] = [];
  $form['search']['#theme_wrappers'] = [];
  $form['search']['#attributes']['value'] = Xss::filter($searched_value);
  $form['search']['#attributes']['name'] = $form['search']['#name'];
  $form['search']['#attributes']['class'] = [];
  $form['search']['#attributes']['id'] = $mobile_version ? 'header-search__mobile' : 'header-search';
  unset($form['search']['#attributes']['data-search-api-autocomplete-search']);
  unset($form['search']['#attributes']['data-autocomplete-path']);
  unset($form['search']['#autocomplete_route_name']);
  unset($form['search']['##autocomplete_route_parameters']);
  unset($form['search']['#value_callback']);

  // Change the submit attributes and properties.
  $form['actions']['#theme_wrappers'] = [];
  $form['actions']['submit']['#theme'] = 'input__submit__search_search_submit';
  $form['actions']['submit']['#theme_wrappers'] = [];
  $form['actions']['submit']['#attributes']['class'] = [];
  $form['actions']['submit']['#attributes']['class'][] = 'header-search__icon';
  $form['actions']['submit']['#required'] = TRUE;
  $form['actions']['submit']['#value'] = t('Search');
  $form['actions']['reset']['#access'] = FALSE;
  unset($form['actions']['submit']['#attributes']['data-bef-auto-submit-click']);

  // This removes ajax elements from the attributes array.
  $form['#attributes']['id'] = $mobile_version ? $form['#id'] . '--mobile' : $form['#id'];
  unset($form['#attributes']['data-auto-submit-full-form']);
  unset($form['#attributes']['data-bef-auto-submit-full-form']);
  unset($form['#attributes']['data-auto-submit']);
  unset($form['#attributes']['data-bef-auto-submit']);
  unset($form['#attributes']['data-auto-submit-delay']);
  unset($form['#attributes']['data-bef-auto-submit-delay']);
  unset($form['#attributes']['data-drupal-selector']);
  unset($form['ajax-submit']);

  // Remove the captcha.
  unset($form['captcha']);

  // Remove the items per page element.
  unset($form['items_per_page']);

  return $form;
}
