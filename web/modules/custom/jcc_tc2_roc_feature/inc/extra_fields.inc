<?php

/**
 * @file
 * Contains jcc_roc extra fields module functionality.
 */

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Build the in page navigation for the rule document node.
 */
function jcc_tc2_roc_feature_rule_index_build_in_page_navigation(EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode): array {
  $roc_rule_link_service = \Drupal::service('jcc_tc2_roc_feature.rule_link.service');

  $items = [];
  $items['roc_main_listing_page'] = $roc_rule_link_service->getRocMainListingPageLink();
  $items['roc_backtotop'] = [
    '#markup' => $roc_rule_link_service->getBackToTopLink(),
    '#wrapper_attributes' => ['class' => 'item__align-right'],
  ];

  $build = [
    '#theme' => 'item_list',
    '#type' => 'ul',
    '#items' => $items,
    '#attributes' => ['class' => 'roc-document-list'],
  ];

  return $build;
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

    $a = array_search($entity->id(), $ordered_list_of_subsections);
    $index = array_search($a, array_keys($ordered_list_of_subsections));
    if ($index != 0) {
      $result_prev = array_key_first(array_slice($ordered_list_of_subsections, $index - 1, 1, TRUE));
      $previous_subsection = $ordered_list_of_subsections[$result_prev];
      $items['roc_previous_rule'] = $roc_rule_link_service->getLinkToRuleSection($previous_subsection, $parent_document, t('Previous rule'));
    }

    $items['roc_main_listing_page'] = $roc_rule_link_service->getLinkToRuleIndexToc($parent_document, t('Back to Table of Contents'));

    if ($index < $number_of_subsections) {
      $result_next = array_key_first(array_slice($ordered_list_of_subsections, $index + 1, 1, TRUE));
      $next_subsection = $ordered_list_of_subsections[$result_next];
      $items['roc_next_rule'] = $roc_rule_link_service->getLinkToRuleSection($next_subsection, $parent_document, t('Next rule'));
    }
  }

  $items['roc_backtotop'] = [
    '#markup' => $roc_rule_link_service->getBackToTopLink(),
    '#wrapper_attributes' => ['class' => 'item__align-right'],
  ];

  return [
    '#theme' => 'item_list',
    '#type' => 'ul',
    '#items' => $items,
    '#attributes' => ['class' => 'cluster detect-wrap detect-wrap--observed roc-document-list'],
  ];

}
