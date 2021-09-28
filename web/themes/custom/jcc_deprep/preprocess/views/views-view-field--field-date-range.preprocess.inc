<?php

/**
 * @file
 * Preprocessors for views.
 */

use Drupal\Core\Render\Markup;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_deprep_preprocess_views_view_field__field_date_range(&$variables) {
  if ($variables['view']->id() == 'events_list'
    && $variables['view']->current_display == 'block_1'
    && $variables['field']->table == 'node__field_date_range') {

    $date_string = $variables['row']->node__field_date_range_field_date_range_value;
    $date = DateTime::createFromFormat('Y-m-d\TH:i:s', $date_string);

    if ($date) {
      $variables['output'] = Markup::create($date->format('M d'));
    }
  }
}
