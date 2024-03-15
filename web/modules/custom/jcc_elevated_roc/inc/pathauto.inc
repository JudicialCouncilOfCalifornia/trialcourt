<?php

/**
 * @file
 * A file for pathauto.
 */

/**
 * Implements hook_pathauto_pattern_alter().
 *
 * Change URL alias patterns for documents marked as "Rule of Court".
 */
function jcc_elevated_roc_pathauto_pattern_alter(&$pattern, array $context): void {
  // If we are processing a document node.
  if (isset($context['data']['node'])) {
    $node = $context['data']['node'];
    if ($node && $node->bundle() == 'document') {

      $roc_rule_service = \Drupal::service('jcc_elevated_roc.rule.service');
      $category_id = $roc_rule_service->getRocDefiningDocumentCategoryId();
      $term = $node->get('field_document_type')->first() ? $node->get('field_document_type')->first()->getValue() : FALSE;

      if (isset($term['target_id']) && ($term['target_id'] == $category_id)) {

        // Set path to follow the Rules of Court base path (/cms/rules/index),
        // and the document_id.
        // This will build a path like "/cms/rules/index/title_one".
        $document_id = $node->get('field_document_id')->first() ? $node->get('field_document_id')->first()->getValue() : FALSE;
        if (isset($document_id['value'])) {
          $base_path = $roc_rule_service->getRocBasePath();
          $pattern->setPattern("$base_path/[node:field_document_id]");
        }
      }
    }
  }
}
