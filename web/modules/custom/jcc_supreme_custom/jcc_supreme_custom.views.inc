<?php

/**
 * @file
 * Views integration for Supreme Court filing periods.
 */

/**
 * Implements hook_views_data_alter().
 */
function jcc_supreme_custom_views_data_alter(array &$data) {
  if (isset($data['node__field_case_initiation_date'])) {
    $data['node__field_case_initiation_date']['jcc_filed_case_period'] = [
      'title' => t('Weekly filing period'),
      'help' => t('Classify cases by filing date at the Tuesday 9 a.m. refresh.'),
      'filter' => [
        'id' => 'jcc_filed_case_period',
        'real field' => 'field_case_initiation_date_value',
      ],
    ];
  }
}
