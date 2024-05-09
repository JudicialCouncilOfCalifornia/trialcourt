<?php

/**
 * @file
 * Contains jcc_tc2_roc_feature entity module functionality.
 */

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_entity_insert().
 */
function jcc_tc2_roc_feature_entity_insert(EntityInterface $entity): void {
  if ($entity->bundle() == 'roc_rule_index') {
    jcc_roc_rule_index_set_pathalias($entity);
  }

  if ($entity->bundle() == 'roc_rule') {
    jcc_roc_rule_set_pathalias($entity);
  }
}

/**
 * Implements hook_entity_update().
 */
function jcc_tc2_roc_feature_entity_update(EntityInterface $entity): void {
  if ($entity->bundle() == 'roc_rule_index') {
    jcc_roc_rule_index_set_pathalias($entity);
  }

  if ($entity->bundle() == 'roc_rule') {
    jcc_roc_rule_set_pathalias($entity);
  }
}

/**
 * Implements hook_ENTITY_TYPE_view().
 */
function jcc_tc2_roc_feature_node_view(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode): void {
  if ($display->getComponent('in_page_nav')) {
    if ($entity->bundle() == 'roc_rule_index') {
      $build['in_page_nav'] = jcc_tc2_roc_feature_rule_index_build_in_page_navigation($entity, $display, $view_mode);
    }

    if ($entity->bundle() == 'roc_rule') {
      $build['in_page_nav'] = jcc_tc2_roc_feature_rule_build_in_page_navigation($entity, $display, $view_mode);
    }
  }
}

/**
 * Implements hook_ENTITY_TYPE_presave().
 */
function jcc_tc2_roc_feature_node_presave(EntityInterface $entity): void {
  if ($entity->bundle() == 'roc_rule_index') {
    $entity_manager = \Drupal::entityTypeManager();
    $node_manager = $entity_manager->getStorage('node');
    $roc_rule_link_service = \Drupal::service('jcc_roc.rule_link.service');

    // Assign the rule document id to all the subsections that are attached.
    $ordered_list_of_subsections = $roc_rule_link_service->getRocRuleIdsFromIndex($entity);
    $number_of_subsections = count($ordered_list_of_subsections);
    if ($number_of_subsections) {
      $subsection_nodes = $node_manager->loadMultiple($ordered_list_of_subsections);
      foreach ($subsection_nodes as $subsection_node) {
        $subsection_node->set('field_roc_rule_parent_index', $entity->id());
        $subsection_node->save();
      }
    }
  }
}
