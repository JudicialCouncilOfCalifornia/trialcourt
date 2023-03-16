<?php

/**
 * @file
 * Preprocess pager.
 */

/**
 * Implements template_preprocess_pager() for pager.html.twig.
 */
function jcc_base_preprocess_pager(&$variables) {

  /** @var \Drupal\Core\Pager\PagerManagerInterface $pager_manager */
  $pager_manager = \Drupal::service('pager.manager');
  $element = $variables['pager']['#element'];
  $pager = $pager_manager->getPager($element);

  $variables['total_pages'] = isset($pager) ? $pager->getTotalPages() : '';
}
