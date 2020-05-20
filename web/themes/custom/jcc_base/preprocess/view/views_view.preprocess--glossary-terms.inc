<?php

/**
* Implements hook_preprocess_HOOK().
*/

function jcc_base_preprocess_views_view_summary_unformatted__glossary_terms(&$variables) {
  $all_row = array('link'=> ' (ALL) ', 'url' => '/glossary', 'separator'=> ' | ');
  array_push($variables['rows'], $all_row);
}
