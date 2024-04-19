<?php

/**
 * @file
 * Contains jcc_roc pathalias module functionality.
 */

use Drupal\Core\Entity\EntityInterface;

/**
 * Sets the path alias for rule subsection content type.
 */
function jcc_roc_rule_subsection_set_pathalias(EntityInterface $entity): void {
  if ($entity->bundle() == 'rule_subsection') {
    $has_parent_document_id = FALSE;
    $has_subsection_id = FALSE;

    $parent_document = $entity->get('field_rule_subsection_document')->referencedEntities();
    if (!empty($parent_document)) {
      $parent_document_id = $parent_document[0]->get('field_rule_document_id')->getValue();
      if (!empty($parent_document_id)) {
        $has_parent_document_id = TRUE;
        $parent_document_id = $parent_document_id[0]['value'];
      }
    }

    $subsection_id = $entity->get('field_rule_subsection_id')->getValue();
    if ($subsection_id) {
      $has_subsection_id = TRUE;
      $subsection_id = $subsection_id[0]['value'];
    }

    if ($has_parent_document_id && $has_subsection_id) {
      $entity_manager = \Drupal::entityTypeManager();
      $alias_manager = $entity_manager->getStorage('path_alias');
      $language_manager = \Drupal::languageManager();
      $langcode = $language_manager->getCurrentLanguage()->getId();
      $alias_manager->create([
        'path' => "/node/" . $entity->id(),
        'alias' => '/cms/rules/index/' . $parent_document_id . '/' . $subsection_id,
        'langcode' => $langcode,
      ])->save();
    }
  }

}

/**
 * Sets the path alias for rule document content type.
 */
function jcc_roc_rule_document_set_pathalias(EntityInterface $entity): void {
  if ($entity->bundle() == 'rule_document') {
    $document_id = $entity->get('field_rule_document_id')->getValue();
    if (!empty($document_id)) {
      $entity_manager = \Drupal::entityTypeManager();
      $alias_manager = $entity_manager->getStorage('path_alias');
      $language_manager = \Drupal::languageManager();
      $langcode = $language_manager->getCurrentLanguage()->getId();
      $alias_manager->create([
        'path' => "/node/" . $entity->id(),
        'alias' => '/cms/rules/index/' . $document_id[0]['value'],
        'langcode' => $langcode,
      ])->save();
    }
  }

}
