<?php

/**
 * @file
 * Preprocess for glossary term view.
 */

/**
 * Implements hook_preprocess().
 */
function jcc_base_preprocess_views_view_summary_unformatted__glossary_terms(&$variables) {
  $all_row = ['link' => ' (ALL) ', 'url' => '/glossary', 'separator' => ' | '];
  array_push($variables['rows'], $all_row);
}
