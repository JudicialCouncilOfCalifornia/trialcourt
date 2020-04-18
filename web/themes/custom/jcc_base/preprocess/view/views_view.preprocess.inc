<?php

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_views_view(&$variables) {
  $view = $variables['view'];
  switch($view->id()) {
    case 'location':
      if ($view->current_display == 'block') {
        foreach($view->result as $row) {
          $variables['sidebar']['rows'][] = $row->_entity;
        }
        $num_rows = $view->total_rows;
        $verb = $num_rows == 1 ? 'is' : 'are';
        $noun = $num_rows == 1 ? 'Location' : 'Locations';
        $replacements = [
          '@verb' => $verb,
          '@num' => $num_rows,
          '@noun' => $noun,
        ];

        $variables['sidebar']['description'] = t('There @verb @num Court @noun', $replacements);
      }
      break;
  }
}
