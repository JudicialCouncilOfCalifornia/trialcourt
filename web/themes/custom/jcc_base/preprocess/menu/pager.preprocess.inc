<?php

/**
 * @file
 * Preprocess pager.
 */

/**
 * Implements template_preprocess_pager() for pager.html.twig.
 */
function jcc_base_preprocess_pager(&$variables) {
  // @see globals.api.php
  global $pager_total;
  $element = $variables['pager']['#element'];
  // Get the max page number for the current pager.
  $variables['total_pages'] = $pager_total[$element];
}
