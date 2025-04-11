<?php

/**
 * @file
 * Generic system element preprocessors.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\Entity\View;
use Symfony\Component\HttpFoundation\Request;

/**
 * Implements hook_preprocess_views_view().
 */
function jcc_elevated_preprocess_pager(&$variables) {
  // Add the current value into items for easier theming.
  $variables['items']['current'] = $variables['current'] ?? 0;

  if (isset($variables['items']['first']['text'])) {
    // Lock the text value for the first.
    $variables['items']['first']['text'] = t('First');
    // To remove the "First" link, uncomment below.
    // $variables['items']['first'] = [];.
  }

  if (isset($variables['items']['previous']['text'])) {
    // Lock the text value for the previous.
    $variables['items']['previous']['text'] = t('Previous');
  }

  if (isset($variables['items']['next']['text'])) {
    // Lock the text value for the next.
    $variables['items']['next']['text'] = t('Next');
  }

  if (isset($variables['items']['last']['text'])) {
    // Lock the text value for the last.
    $variables['items']['last']['text'] = t('Last');
    // To remove the "Last" link, uncomment below.
    // $variables['items']['last'] = [];.
  }
}

/**
 * Implements hook_preprocess_breadcrumb().
 */
function jcc_elevated_preprocess_breadcrumb(&$variables) {
  $add_homepage_to_breadcrumb = TRUE;

  // We are creating a variable for the Current Page Title, to allow us to print
  // it after the breadcrumbs loop has run.
  $route_match = \Drupal::routeMatch();
  $title = \Drupal::service('title_resolver')->getTitle(\Drupal::request(), $route_match->getRouteObject());
  $variables['current_page_title'] = $title;

  // Remove items that don't have a url.
  $count = count($variables['breadcrumb']);
  if ($count > 0) {
    foreach ($variables['breadcrumb'] as $key => $crumb) {
      // If not the last item in the array.
      if ($key != ($count - 1)) {
        if (empty($crumb['url'])) {
          unset($variables['breadcrumb'][$key]);
        }
      }
    }
  }

  // Adjust for site sections.
  $section_homepage_url = NULL;
  if (function_exists('jcc_get_current_page_section') && $section_id = jcc_get_current_page_section()) {
    // Since this is a section, do not auto add the "Home" item.
    $add_homepage_to_breadcrumb = FALSE;

    // Replace the first item with a link to the section homepage.
    $node_manager = \Drupal::entityTypeManager()->getStorage('node');
    $section_service = Drupal::service('jcc_elevated_sections.service');
    $section = $section_service->getSectionInfo($section_id);
    $section_homepage_nid = $section->jcc_section_homepage->target_id;
    $section_homepage_node = $node_manager->load($section_homepage_nid);
    $section_homepage_url = Url::fromRoute('entity.node.canonical', ['node' => $section_homepage_nid]);
    $variables['breadcrumb'][0]['text'] = $section_homepage_node->label();
    $variables['breadcrumb'][0]['url'] = $section_homepage_url->toString();

    // If the last item and first item are essentially the same thing, remove
    // the first item. This only applies for sections because the section home
    // is duplicated on the breadcrumb because the default behavior is to add
    // the title of the current page to the end of the breadcrumb, and we have
    // already set the same value as the new home/start link in the breadcrumb.
    $last = end($variables['breadcrumb']);
    if ($variables['breadcrumb'][0]['text'] == $last['text']) {
      unset($variables['breadcrumb'][0]);
    }

    $labels = [];
    $urls = [];
    foreach ($variables['breadcrumb'] as $delta => $item) {
      if (in_array($item['text'], $labels) && in_array($item['url'], $urls)) {
        // If the text already exists in the array, remove the duplicate.
        unset($variables['breadcrumb'][$delta]);
      }
      else {
        $labels[] = $item['text'];
        $urls[] = $item['url'];
      }
    }
  }

  // Previous link alternative for pages without navigation structure.
  // Ignores external referrers to keep handling/context simple.
  $domain_exp = '/^(?:https?:\/\/)?(?:[^@\/\n]+@)?(?:www\.)?([^:\/?\n]+)/';
  $referrer_url = \Drupal::request()->headers->get('referer');
  preg_match($domain_exp, $referrer_url, $referrer_domain);
  $current_domain = \Drupal::request()->getHost();

  $entity = NULL;
  if ($referrer_domain[1] == $current_domain) {
    foreach ($route_match->getParameters() as $parameter) {
      if ($parameter instanceof EntityInterface) {
        $entity = $parameter;
        break;
      }
    }
  }

  $route_name = $route_match->getRouteName();
  $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
  $isMenuItem = $entity ? $menu_link_manager->loadLinksByRoute($route_name, [$entity->getEntityTypeId() => $entity->id()]) : NULL;

  if (!$isMenuItem) {
    $add_homepage_to_breadcrumb = FALSE;
    $variables['breadcrumb'] = [];
    $default_title = t('Go to the homepage');
    $default_url = '/';
    $referrer_title = t('Back to');
    $referrer_url = \Drupal::request()->server->get('HTTP_REFERER');

    if ($referrer_url) {
      $request = Request::create($referrer_url);
      $url_object = \Drupal::service('path.validator')->getUrlIfValid($request->getRequestUri());

      if ($url_object) {
        // Get name of referring local page else link to home.
        $referrer_params = $url_object->getRouteParameters();
        $referrer_entity_type = key($referrer_params);
        $referrer_entity_param = key($referrer_params);
        if (key($referrer_params) == 'view_id') {
          $referrer_entity_type = 'view';
          $referrer_entity_param = 'view_id';
        }
        $referrer_entity = \Drupal::entityTypeManager()->getStorage($referrer_entity_type)->load($referrer_params[$referrer_entity_param]);
        $current_route_params = \Drupal::routeMatch()->getParameters();
        $current_entity = reset($current_route_params);
        $current_entity = reset($current_entity);
        $referrer_not_self = $referrer_entity->uuid() != $current_entity->uuid();
        if ($referrer_entity instanceof View) {
          $referrer_title = $referrer_title . ' ' . $referrer_entity->label() . ' ' . t('management');
        }
        elseif (($entity instanceof Term || $entity instanceof Media) && $referrer_not_self) {
          $referrer_title = $referrer_title . ' ' . $referrer_entity->getName();
        }
        elseif ($entity instanceof Node && $referrer_not_self) {
          // New nodes missing referrer info on create until page refresh.
          // Set to content management page when no referrer info or redirect.
          $node_title = $referrer_entity->getEntityTypeId() != 'node_type' ? $referrer_entity->getTitle() : 'Content management';
          $referrer_title = $referrer_title . ' ' . $node_title;
          $referrer_url = $referrer_entity->getEntityTypeId() != 'node_type' ? $referrer_url : '/admin/content';
        }
        else {
          $referrer_title = $default_title;
          $referrer_url = $default_url;

          // If section homepage.
          if (function_exists('jcc_get_current_page_section') && $section_id = jcc_get_current_page_section() && $section_homepage_url) {
            $referrer_url = $section_homepage_url;
          }
        }
      }
    }
    else {
      $referrer_title = $default_title;
      $referrer_url = $default_url;

      // If section homepage.
      if (function_exists('jcc_get_current_page_section') && $section_id = jcc_get_current_page_section() && $section_homepage_url) {
        $referrer_url = $section_homepage_url;
      }
    }

    $variables['breadcrumb'][] = [
      'text' => '< ' . $referrer_title,
      'url' => $referrer_url,
    ];
  }

  // Add homepage link to start of breadcrumb.
  if ($add_homepage_to_breadcrumb) {
    $url = Url::fromRoute('<front>');
    array_unshift($variables['breadcrumb'], [
      'text' => t('Home'),
      'url' => $url->toString(),
    ]);
  }

  // Since we are printing the 'Current Page Title', add the URL cache context.
  // If we don't, then we might end up with something like
  // "Home > Articles" on the Recipes page, which should read "Home > Recipes".
  $variables['#cache']['contexts'][] = 'url';
}

/**
 * Implements template_preprocess_form_element().
 */
function jcc_elevated_preprocess_form_element(&$variables) {
  $variables['attributes']['class'] = 'placeholder-container';
}

/**
 * Implements template_preprocess_input().
 */
function jcc_elevated_preprocess_input(&$variables) {
  $variables['element']['#placeholder'] = '';
}

/**
 * Implements template_preprocess_textarea().
 */
function jcc_elevated_preprocess_textarea(&$variables) {
  $variables['element']['#placeholder'] = '';
}

/**
 * Implements hook_preprocess_preprocess_html().
 */
function jcc_elevated_preprocess_html(&$variables): void {
  // Set some variables.
  $entity_type_manager = \Drupal::service('entity_type.manager');
  $node_manager = $entity_type_manager->getStorage('node');
  $route_match = \Drupal::routeMatch();
  $current_route_name = \Drupal::service('current_route_match')->getRouteName();

  // If the node is a solicitation request node, set the page html title.
  if ($node = $route_match->getParameter('node')) {
    if ($node instanceof NodeInterface) {

      if ($node->bundle() == 'request') {
        $items = [];

        $items['title'] = $node->getTitle();

        if ($node->hasField('field_rfp_number')) {
          $request_number = $node->get('field_rfp_number')->first() ? $node->get('field_rfp_number')->first()->getValue() : FALSE;
          if (isset($request_number['value'])) {
            $items['number'] = '#ID ' . $request_number['value'];
          }
        }

        if ($node->hasField('field_rfp_type')) {
          $request_type = $node->get('field_rfp_type')->first() ? $node->get('field_rfp_type')->first()->view('full') : FALSE;
          if (isset($request_type['#markup'])) {
            $items['type'] = $request_type['#markup']->__toString();
          }
        }

        $site_name = \Drupal::config('system.site')->get('name');
        $items['site_name'] = $site_name;

        $variables['head_title']['title'] = implode(' | ', $items);
      }

    }
  }

  // Adjust the browser tab title for past meetings.
  if ($current_route_name == 'view.advisory_body_events.page_1') {
    // Set our default title.
    $variables['head_title']['title'] = t('Past meetings | California Courts');

    // Check if we get a node in the argument and it is an advisory body.
    if ($nid = $route_match->getParameter('node')) {
      $node = is_numeric($node) ? $node_manager->load($nid) : FALSE;
      if ($node instanceof NodeInterface && $node->getType() == 'advisory_body') {
        $variables['head_title']['title'] = t('Past meetings | @title  | California Courts', ['@title' => $node->label()]);
      }
    }
  }
}
