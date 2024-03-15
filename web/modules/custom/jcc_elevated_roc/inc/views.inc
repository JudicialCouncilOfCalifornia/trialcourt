<?php

/**
 * @file
 * A file for view and query alters.
 */

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_query_alter().
 */
function jcc_elevated_roc_views_query_alter(ViewExecutable $view, QueryPluginBase $query): void {
  // Since we don't know yet the exact taxonomy term (or even vocabulary) that
  // will be the tagger of 'file' media items to include in the rules of court,
  // We don't bother applying the term filter in the view through the UI,
  // instead we modify the query here where we can pull the term, vocab, and
  // field that we want to use as our identifier of court rules.
  if ($view->id() == 'roc_view') {

    // Add code here to get the desired Rule category.
    $roc_rule_service = \Drupal::service('jcc_elevated_roc.rule.service');
    $rule_id = $roc_rule_service->getRocDefiningDocumentCategoryId();

    $definition = [
      'type' => 'INNER',
      'table' => 'node__field_document_type',
      'field' => 'entity_id',
      'left_table' => 'node_field_data',
      'left_field' => 'nid',
      'extra' => [
        0 => [
          'field' => 'deleted',
          'value' => '0',
        ],
        1 => [
          'field' => 'bundle',
          'value' => 'document',
        ],
      ],
    ];

    $join = Drupal::service('plugin.manager.views.join')->createInstance('standard', $definition);
    $query->addRelationship('node__field_document_type', $join, 'media');
    $query->addWhere(1, 'node__field_document_type.field_document_type_target_id', [$rule_id], 'IN');
  }
}
