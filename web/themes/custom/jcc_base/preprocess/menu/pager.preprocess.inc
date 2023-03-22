<?php

/**
 * @file
 * Preprocess pager.
 */

/**
 * Implements template_preprocess_pager() for pager.html.twig.
 */
function jcc_base_preprocess_pager(&$variables) {
  if ($pager = \Drupal::database()->getPagerManager()->getPager()) {
    $variables['total_pages'] = $pager->getTotalPages();
  }
}
