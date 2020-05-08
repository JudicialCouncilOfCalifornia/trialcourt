<?php

/**
 * @file
 * Contains jcc_blocks.module.
 */

/**
 * Implements hook_views_query_alter().
 */
function jcc_announcement_visibility_views_query_alter(Drupal\views\ViewExecutable $view, Drupal\views\Plugin\views\query\Sql $query){
  $t = 1;
  if ($view->storage->get('id') === 'announcements' && $view->current_display === "block_1") {

    // Traverse through the 'where' part of the query.
    foreach ($query->where as &$condition_group) {
      foreach ($condition_group['conditions'] as &$condition) {
        if ($condition['field'] == 'node__field_display_on_all_pages.field_display_on_all_pages_value = :node__field_display_on_all_pages_field_display_on_all_pages_value1') {
//          $field_value = array(':node__field_display_on_all_pages_field_display_on_all_pages_value' => 1);
//          $condition = array(
//            'field' => 'node__field_display_on_all_pages.field_display_on_all_pages_value = :node__field_display_on_all_pages_field_display_on_all_pages_value1',
//            'value' => $field_value,
//            'operator' => '=',
//          );
          $t = 1;
        }
      }
    }
  }
}
