<?php

/**
 * @file
 * A file for basic theme overrides.
 */

/**
 * Implements hook_theme().
 */
function jcc_elevated_roc_theme($existing, $type, $theme, $path): array {

  $roc_path = $path . '/templates';

  return [
    'block__jcc_elevated_roc_rule_list' => [
      'variables' => [],
      'template' => 'block--jcc-elevated-roc-rule-list',
      'base hook' => 'block',
      'path' => $roc_path,
    ],
    'jcc_roc_rules_list' => [
      'render element' => 'children',
      'template' => 'jcc-roc-rules-list',
      'path' => $roc_path,
      'variables' => [
        'document_list' => [],
      ],
    ],
    'jcc_roc_rule_toc' => [
      'render element' => 'children',
      'template' => 'jcc-roc-rule-toc',
      'path' => $roc_path,
      'variables' => [
        'rule_toc' => [],
        'hero_title' => [],
        'upper_links' => [],
        'lower_links' => [],
      ],
    ],
    'jcc_roc_rule' => [
      'render element' => 'children',
      'template' => 'jcc-roc-rule',
      'path' => $roc_path,
      'variables' => [
        'rule_text' => '',
        'hero_title' => [],
        'upper_links' => [],
        'lower_links' => [],
      ],
    ],
  ];

}

/**
 * Implements hook_preprocess_page().
 */
function jcc_elevated_roc_preprocess_page(array &$variables): void {
  $variables['#attached']['library'][] = 'jcc_elevated_roc/jcc_elevated_roc';
}

/**
 * Implements hook_theme_suggestions_node_alter().
 */
function jcc_elevated_roc_theme_suggestions_node_alter(array &$suggestions, array $variables): void {
  if ($node = \Drupal::routeMatch()->getParameter('node')) {
    if ($node->bundle() == 'document') {
      $roc_rule_service = \Drupal::service('jcc_elevated_roc.rule.service');
      $category_id = $roc_rule_service->getRocDefiningDocumentCategoryId();
      $term = $node->get('field_document_type')->first() ? $node->get('field_document_type')->first()->getValue() : FALSE;
      if ($term['target_id'] && ($term['target_id'] == $category_id)) {
        $suggestions[] = 'node__document__roc';
      }
    }
  }
}
