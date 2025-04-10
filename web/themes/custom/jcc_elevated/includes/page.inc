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
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\views\Views;

/**
 * Implements hook_preprocess_HOOK().
 *
 * @throws \Drupal\Core\Form\FormAjaxException
 */
function jcc_elevated_preprocess_page(&$variables) {
  // Check if front page.
  $is_front = \Drupal::service('path.matcher')->isFrontPage();
  $state = \Drupal::state();

  // Determine entity type.
  $entity_type = NULL;
  $route_name = \Drupal::routeMatch()->getRouteName();
  $route_name = explode('.', $route_name);
  if ($route_name[0] == 'entity') {
    $entity_type = $route_name[1];
  }

  $variables['logo'] = theme_get_setting('logo', 'jcc_elevated');

  $variables['site_name'] = [
    'first' => theme_get_setting('site_name_first', 'jcc_elevated'),
    'second' => theme_get_setting('site_name_second', 'jcc_elevated'),
  ];

  // Primary navigation and mobile version of links.
  $variables['main_links'] = jcc_elevated_get_menu_array('main', TRUE);
  $variables['mobile_main_links'] = jcc_elevated_get_menu_array('main', TRUE, TRUE);

  // Utility/featured links.
  $variables['featured_links'] = jcc_elevated_get_menu_array('featured-links');

  // Footer menu links.
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

  // BEGIN Translate features.
  // @todo Add Drupal translate menu to array.
  $variables['translate'] = [];
  // Google Translate.
  if (theme_get_setting('show_google_translate')) {
    $google_translate = \Drupal::service('plugin.manager.block')->createInstance('google_translate_block', []);
    if (isset($google_translate) && !empty($google_translate)) {
      $variables['translate'][] = $google_translate->build();
    }
  }

  // Display content only when embed param is 1.  Hide header and footer.
  $variables['embed'] = \Drupal::request()->query->get('embed') == 1;

  // Set breadcrumb or previous link.
  // See breadcrumb hook for granular conditions.
  $display_breadcrumb = NULL;
  if ($entity_type == 'node' || $entity_type == 'media') {
    $entity = \Drupal::routeMatch()->getParameter($entity_type);
    if ($entity) {
      $display_breadcrumb = TRUE;
    }
  }
  $suppress_from = [
    'roc_rule_index',
    'roc_rule',
  ];
  if ($display_breadcrumb && !$is_front && !in_array($entity->bundle(), $suppress_from)) {
    $variables['breadcrumb'] = \Drupal::service('breadcrumb')->build(\Drupal::routeMatch())->toRenderable();
  }

  $social_links = [];
  $social_link_ids = [
    'facebook' => 'jcc_elevated.social_links_facebook',
    'linkedin' => 'jcc_elevated.social_links_linkedin',
    'rss' => 'jcc_elevated.social_links_rss',
    'twitter' => 'jcc_elevated.social_links_twitter',
    'youtube' => 'jcc_elevated.social_links_youtube',
    'flickr' => 'jcc_elevated.social_links_flickr',
  ];

  $frontpage_path = \Drupal::urlGenerator()->generateFromRoute('<front>', [], ['absolute' => TRUE]);

  foreach ($social_link_ids as $name => $id) {
    $url_value = $state->get($id, NULL);
    if (!empty($url_value)) {
      $social_links[$name] = [
        'text' => ucfirst($name),
        'url' => $url_value,
        'icon' => 'fa-' . $name,
        'icon_path' => $frontpage_path . 'themes/contrib/jcc_storybook/src/assets/icons.svg',
      ];
    }
  }

  $variant = $state->get('jcc_elevated.footer_variant', 'default');
  $upper_menu = $state->get('jcc_elevated.upper_footer_menu', 'footer');
  $lower_menu = $state->get('jcc_elevated.lower_footer_menu', '_none_');
  $about_header = $state->get('jcc_elevated.about_header', '');
  $about_text = $state->get('jcc_elevated.about_text', [
    'value' => '',
    'format' => 'snippet',
  ]);
  $about_link_label = $state->get('jcc_elevated.about_link_title', '');
  $about_link_url = $state->get('jcc_elevated.about_link_url', '');
  $display_footer_lower = $lower_menu != '_none_' ?? FALSE;

  $variables['footer_values'] = [
    'variant' => $variant,
    'display_footer_lower' => $display_footer_lower,
    'upper_footer_menu' => !empty($upper_menu) ? jcc_elevated_get_menu_array($upper_menu) : [],
    'lower_footer_menu' => !empty($lower_menu) ? jcc_elevated_get_menu_array($lower_menu, TRUE) : [],
    'about_label' => $about_header,
    'about_content' => isset($about_text['value']) ? Markup::create($about_text['value']) : '',
    'about_link_label' => $about_link_label,
    'about_link_href' => $about_link_url,
    'social_links' => $social_links,
  ];
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
function jcc_elevated_get_menu_array(string $menu_name, bool $expanded = FALSE, $add_overview_links = FALSE): array {
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
  return jcc_elevated_menu_to_array($tree, $menu_name, $add_overview_links, $expanded);
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
function jcc_elevated_menu_to_array(array $tree, string $menu_name, bool $add_overview_links = FALSE, bool $expanded = FALSE): array {
  $links = [];

  foreach ($tree as $element) {
    // Don't include links the user doesn't have access to.
    if (!$element->access->isAllowed()) {
      continue;
    }

    // Build the sublinks array.
    $sublinks = [];
    $section_link = [];
    if ($expanded && $element->depth = 1 && $element->hasChildren && $add_overview_links) {
      $sublinks = jcc_elevated_menu_to_array($element->subtree, $menu_name, $add_overview_links);

      $section_link = [
        'text' => $element->link->getTitle(),
        'url' => $element->link->getUrlObject(),
        'is_current' => $element->inActiveTrail,
        'is_overview_link' => TRUE,
        'links' => [],
      ];

      array_unshift($sublinks, $section_link);
    }
    elseif ($expanded && $element->depth = 1 && $element->hasChildren) {
      $sublinks = jcc_elevated_menu_to_array($element->subtree, $menu_name, $add_overview_links);
    }

    $links[] = [
      'text' => $element->link->getTitle(),
      'url' => $element->link->getUrlObject(),
      'is_current' => $element->inActiveTrail,
      'is_overview_link' => FALSE,
      'links' => $sublinks,
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
  if ($searched_value !== NULL && !is_array($searched_value)) {
    $form['search']['#attributes']['value'] = Xss::filter($searched_value);
  }
  $form['search']['#attributes']['name'] = $form['search']['#name'];
  $form['search']['#attributes']['class'] = [];
  $form['search']['#attributes']['aria-label'] = t('Search') . ' ' . \Drupal::request()->getHost();
  $form['search']['#attributes']['placeholder'] = t('Search') . ' ' . \Drupal::request()->getHost();
  $form['search']['#attributes']['id'] = $mobile_version ? 'header-search__mobile' : 'header-search';
  unset($form['search']['#attributes']['data-search-api-autocomplete-search']);
  unset($form['search']['#attributes']['data-autocomplete-path']);
  unset($form['search']['#autocomplete_route_name']);
  unset($form['search']['##autocomplete_route_parameters']);
  unset($form['search']['#value_callback']);

  // Change the submit attributes and properties.
  $form['actions']['#theme_wrappers'] = [];
  $form['actions']['submit']['#theme'] = 'input__submit__search_search_submit__header';
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
