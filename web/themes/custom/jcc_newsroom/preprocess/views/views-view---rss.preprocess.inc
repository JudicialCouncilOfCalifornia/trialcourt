<?php

/**
 * @file
 * Preprocessors for views.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_newsroom_preprocess_views_view_rss(&$variables) {
  if (!(\Drupal::service('twig')->isDebug())) {
    $variables['view']->getResponse()->headers->set('Content-Type', 'text/xml;charset=UTF-8');
  }
}
