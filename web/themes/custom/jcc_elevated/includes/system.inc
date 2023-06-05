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

  // Add homepage link to start of breadcrumb.
  $url = Url::fromRoute('<front>');
  array_unshift($variables['breadcrumb'], [
    'text' => t('Home'),
    'url' => $url->toString(),
  ]);

  // Since we are printing the 'Current Page Title', add the URL cache context.
  // If we don't, then we might end up with something like
  // "Home > Articles" on the Recipes page, which should read "Home > Recipes".
  $variables['#cache']['contexts'][] = 'url';
}
