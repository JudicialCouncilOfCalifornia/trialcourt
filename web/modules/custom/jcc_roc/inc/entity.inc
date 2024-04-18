<?php

/**
 * @file
 * Contains jcc_roc entity module functionality.
 */

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_entity_insert().
 */
function jcc_roc_entity_insert(EntityInterface $entity): void {
  if ($entity->bundle() == 'rule_document') {
    jcc_roc_rule_document_set_pathalias($entity);
  }

  if ($entity->bundle() == 'rule_subsection') {
    jcc_roc_rule_subsection_set_pathalias($entity);
  }
}

/**
 * Implements hook_entity_update().
 */
function jcc_roc_entity_update(EntityInterface $entity): void {
  if ($entity->bundle() == 'rule_document') {
    jcc_roc_rule_document_set_pathalias($entity);
  }

  if ($entity->bundle() == 'rule_subsection') {
    jcc_roc_rule_subsection_set_pathalias($entity);
  }
}

/**
 * Implements hook_ENTITY_TYPE_view().
 */
function jcc_roc_node_view(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode): void {
  if ($display->getComponent('in_page_nav')) {
    if ($entity->bundle() == 'rule_document') {
      $build['in_page_nav'] = jcc_roc_rule_document_build_in_page_navigation($entity, $display, $view_mode);
    }

    if ($entity->bundle() == 'rule_subsection') {
      $build['in_page_nav'] = jcc_roc_rule_subsection_build_in_page_navigation($entity, $display, $view_mode);
    }
  }
}

/**
 * Implements hook_ENTITY_TYPE_presave().
 */
function jcc_roc_node_presave(EntityInterface $entity): void {
  if ($entity->bundle() == 'rule_document') {
    $entity_manager = \Drupal::entityTypeManager();
    $node_manager = $entity_manager->getStorage('node');
    $roc_rule_link_service = \Drupal::service('jcc_roc.rule_link.service');

    // Assign the rule document id to all the subsections that are attached.
    $ordered_list_of_subsections = $roc_rule_link_service->getRocSubSectionIdsFromDocument($entity);
    $number_of_subsections = count($ordered_list_of_subsections);
    if ($number_of_subsections) {
      $subsection_nodes = $node_manager->loadMultiple($ordered_list_of_subsections);
      foreach ($subsection_nodes as $subsection_node) {
        $subsection_node->set('field_rule_subsection_document', $entity->id());
        $subsection_node->save();
      }
    }
  }
}
