<?php

/**
 * @file
 * Post Updates for immutable.
 */

/**
 * Rebuild permissions after updating nodeaccess to v2.
 */
function jcc_tc2_all_immutable_config_post_update_rebuild_permissions(): void {
  // Run the permissions rebuild. This forces the permissions for each node to
  // be reprocessed into the node_access table.
  node_access_rebuild(TRUE);
}

/**
 * Adds Attorney of Record to the Case view table output.
 */
function jcc_tc2_all_immutable_config_post_update_case_view_attorney_of_record(): void {
  $config = \Drupal::configFactory()->getEditable('views.view.case');
  $data = $config->getRawData();
  if (empty($data['display']['default']['display_options'])) {
    return;
  }

  $display_options = &$data['display']['default']['display_options'];
  $fields = &$display_options['fields'];

  // If the field is already present, avoid rewriting the config.
  if (isset($fields['field_attorney_of_record'])) {
    return;
  }

  $fields['field_attorney_of_record'] = [
    'id' => 'field_attorney_of_record',
    'table' => 'node__field_attorney_of_record',
    'field' => 'field_attorney_of_record',
    'relationship' => 'none',
    'group_type' => 'group',
    'admin_label' => '',
    'plugin_id' => 'field',
    'label' => 'Attorney of Record',
    'exclude' => FALSE,
    'alter' => [
      'alter_text' => FALSE,
      'text' => '',
      'make_link' => FALSE,
      'path' => '',
      'absolute' => FALSE,
      'external' => FALSE,
      'replace_spaces' => FALSE,
      'path_case' => 'none',
      'trim_whitespace' => FALSE,
      'alt' => '',
      'rel' => '',
      'link_class' => '',
      'prefix' => '',
      'suffix' => '',
      'target' => '',
      'nl2br' => FALSE,
      'max_length' => 0,
      'word_boundary' => TRUE,
      'ellipsis' => TRUE,
      'more_link' => FALSE,
      'more_link_text' => '',
      'more_link_path' => '',
      'strip_tags' => FALSE,
      'trim' => FALSE,
      'preserve_tags' => '',
      'html' => FALSE,
    ],
    'element_type' => '',
    'element_class' => '',
    'element_label_type' => '',
    'element_label_class' => '',
    'element_label_colon' => TRUE,
    'element_wrapper_type' => '',
    'element_wrapper_class' => '',
    'element_default_classes' => TRUE,
    'empty' => '',
    'hide_empty' => FALSE,
    'empty_zero' => FALSE,
    'hide_alter_empty' => TRUE,
    'click_sort_column' => 'value',
    'type' => 'basic_string',
    'settings' => [],
    'group_column' => 'value',
    'group_columns' => [],
    'group_rows' => TRUE,
    'delta_limit' => 0,
    'delta_offset' => 0,
    'delta_reversed' => FALSE,
    'delta_first_last' => FALSE,
    'multi_type' => 'separator',
    'separator' => ', ',
    'field_api_classes' => FALSE,
  ];

  $display_options['style']['options']['columns']['field_attorney_of_record'] = 'field_attorney_of_record';
  $display_options['style']['options']['info']['field_attorney_of_record'] = [
    'sortable' => TRUE,
    'default_sort_order' => 'asc',
    'align' => '',
    'separator' => '',
    'empty_column' => TRUE,
    'responsive' => '',
  ];

  $dependencies = &$data['dependencies']['config'];
  if (!in_array('field.storage.node.field_attorney_of_record', $dependencies, TRUE)) {
    $dependencies[] = 'field.storage.node.field_attorney_of_record';
  }

  foreach (['default', 'block_1'] as $display_id) {
    $tags = &$data['display'][$display_id]['cache_metadata']['tags'];
    if (is_array($tags) && !in_array('config:field.storage.node.field_attorney_of_record', $tags, TRUE)) {
      $tags[] = 'config:field.storage.node.field_attorney_of_record';
    }
  }

  $config->setData($data)->save(TRUE);
}
