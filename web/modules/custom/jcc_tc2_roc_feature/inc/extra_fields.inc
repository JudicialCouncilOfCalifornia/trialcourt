<?php

/**
 * @file
 * Contains jcc_tc2_roc_feature extra fields module functionality.
 */

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Build the in page navigation for the rule document node.
 */
function jcc_tc2_roc_feature_rule_index_build_in_page_navigation(EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode): array {
  $roc_rule_link_service = \Drupal::service('jcc_tc2_roc_feature.rule_link.service');

  $items = [];
  $items['index_link'] = $roc_rule_link_service->getRocMainListingPageLink();
  $items['back_to_top'] = [
    '#markup' => $roc_rule_link_service->getBackToTopLink(),
    '#wrapper_attributes' => ['class' => 'item__align-right'],
  ];

  return [
    '#theme' => 'in_page_nav',
    '#items' => $items,
    '#wrapper_attributes' => ['class' => 'box roc-rule-toc__nav-links upper-links'],
    '#attributes' => ['class' => 'container cluster detect-wrap detect-wrap--observed roc-document-list'],
  ];
}

/**
 * Build the in page navigation for the rule document node.
 */
function jcc_tc2_roc_feature_rule_build_in_page_navigation(EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode): array {
  $roc_rule_link_service = \Drupal::service('jcc_tc2_roc_feature.rule_link.service');
  $parent_document = $roc_rule_link_service->getRocIndexFromRule($entity);

  $items = [];

  if ($parent_document) {
    $ordered_list_of_subsections = $roc_rule_link_service->getRocRuleIdsFromIndex($parent_document);
    $number_of_subsections = count($ordered_list_of_subsections);
    $items['previous'] = FALSE;

    $a = array_search($entity->id(), $ordered_list_of_subsections);
    $index = array_search($a, array_keys($ordered_list_of_subsections));
    if ($index != 0) {
      $result_prev = array_key_first(array_slice($ordered_list_of_subsections, $index - 1, 1, TRUE));
      if ($result_prev) {
        $previous_subsection = $ordered_list_of_subsections[$result_prev];
        $items['previous'] = $roc_rule_link_service->getLinkToRuleSection($previous_subsection, $parent_document, t('Previous rule'));
      }
    }

    $items['index_link'] = $roc_rule_link_service->getLinkToRuleIndexToc($parent_document, t('Back to Table of Contents'));

    $items['next'] = FALSE;
    if ($index < $number_of_subsections) {
      $result_next = array_key_first(array_slice($ordered_list_of_subsections, $index + 1, 1, TRUE));
      if ($result_next) {
        $next_subsection = $ordered_list_of_subsections[$result_next];
        $items['next'] = $roc_rule_link_service->getLinkToRuleSection($next_subsection, $parent_document, t('Next rule'));
      }
    }
  }

  $items['back_to_top'] = [
    '#markup' => $roc_rule_link_service->getBackToTopLink(),
    '#wrapper_attributes' => ['class' => 'item__align-right'],
  ];

  return [
    '#theme' => 'in_page_nav',
    '#type' => 'ul',
    '#items' => $items,
    '#wrapper_attributes' => ['class' => 'box roc-rule-toc__nav-links upper-links'],
    '#attributes' => ['class' => 'container cluster detect-wrap detect-wrap--observed roc-document-list'],
  ];
}
