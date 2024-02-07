<?php

/**
 * @file
 * Generic system element preprocessors.
 */

use Drupal\Core\Url;

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

  // We are creating a variable for the Current Page Title, to allow us to print
  // it after the breadcrumbs loop has run.
  $route_match = \Drupal::routeMatch();
  $title = \Drupal::service('title_resolver')
    ->getTitle(\Drupal::request(), $route_match->getRouteObject());
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

  $add_homepage_to_breadcrumb = TRUE;

  if (function_exists('jcc_get_current_page_section') && $section_id = jcc_get_current_page_section()) {
    // Since this is a section, do not auto add the "Home" item.
    $add_homepage_to_breadcrumb = FALSE;

    // Replace the first item with a link to the section homepage.
    $node_manager = \Drupal::entityTypeManager()->getStorage('node');
    $section_service = Drupal::service('jcc_elevated_sections.service');
    $section = $section_service->getSectionInfo($section_id);
    $section_homepage_nid = $section->jcc_section_homepage->target_id;
    $section_homepage_node = $node_manager->load($section_homepage_nid);
    $url = Url::fromRoute('entity.node.canonical', ['node' => $section_homepage_nid]);
    $variables['breadcrumb'][0]['text'] = $section_homepage_node->label();
    $variables['breadcrumb'][0]['url'] = $url->toString();

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

  // Add homepage link to start of breadcrumb.
  if ($add_homepage_to_breadcrumb) {
    $url = Url::fromRoute('<front>');
    array_unshift($variables['breadcrumb'], [
      'text' => t('Home'),
      'url' => $url->toString(),
    ]);
  }

  // Remove breadcrumb if only 1 item.
  if (count($variables['breadcrumb']) <= 1) {
    $variables['breadcrumb'] = [];
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
