<?php

/**
 * @file
 * Contains jcc_tc2_roc_feature preprocess module functionality.
 */

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;

/**
 * Implements hook_preprocess_page().
 */
function jcc_tc2_roc_feature_preprocess_page(array &$variables): void {
  $is_admin = \Drupal::service('router.admin_context')->isAdminRoute();
  if ($is_admin) {
    $variables['#attached']['library'][] = 'jcc_tc2_roc_feature/jcc-roc-admin-custom';
  }

  if (!$is_admin) {
    $variables['#attached']['library'][] = 'jcc_tc2_roc_feature/jcc-roc-custom';

    // Determine if page is a rules of court related node.
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof NodeInterface) {
      $bundle = $node->bundle();
      if ($bundle == 'roc_rule' || $bundle == 'roc_rule_index') {
        $variables['hide_was_this_helpful'] = FALSE;
      }
    }
  }
}

/**
 * Implements hook_preprocess_node().
 */
function jcc_tc2_roc_feature_preprocess_node(array &$variables): void {
  $variables['is_admin'] = \Drupal::service('router.admin_context')->isAdminRoute();

  $node = $variables['node'];
  if ($node->bundle() == 'roc_rule_index') {
    $variables['content']['field_roc_rule_revised_date']['#label_display'] = 'hidden';
    if (isset($variables['content']['field_roc_rule_revised_date'][0]['#text'])) {
      $variables['content']['field_roc_rule_revised_date'][0]['#text'] = t('Current as of') . ' ' . $variables['content']['field_roc_rule_revised_date'][0]['#text'];
    }

    // Change the media link to go directly to the file and not the media item.
    if (isset($variables['content']['field_roc_rule_media'][0]['#url'])) {
      if (isset($variables['content']['field_roc_rule_media'][0]['#options']['entity'])) {
        $roc_rule_service = \Drupal::service('jcc_tc2_roc_feature.rule.service');
        $file = $roc_rule_service->getMediaDocumentFile($variables['content']['field_roc_rule_media'][0]['#options']['entity']);
        $variables['content']['field_roc_rule_media'][0]['#url'] = Url::fromUserInput($file->createFileUrl());
      }
    }
  }

  if ($node->bundle() == 'roc_rule') {
    if (isset($variables['content']['body'])) {
      // Remove all &nbsp; items from the body content.
      $variables['content']['body'][0]['#text'] = jcc_tc2_roc_feature_filter_nbsp($variables['content']['body'][0]['#text']);
    }
  }
}

/**
 * Implements hook_preprocess_paragraph().
 */
function jcc_tc2_roc_feature_preprocess_paragraph(array &$variables): void {
  $variables['is_admin'] = \Drupal::service('router.admin_context')->isAdminRoute();
  $variables['is_edit'] = jcc_tc2_roc_feature_is_edit_route();
}

/**
 * Implements hook_preprocess_layout_paragraphs().
 */
function jcc_tc2_roc_feature_preprocess_layout_paragraphs(array &$variables): void {
  $variables['is_admin'] = \Drupal::service('router.admin_context')->isAdminRoute();
  $variables['is_edit'] = jcc_tc2_roc_feature_is_edit_route();
}

/**
 * Implements hook_preprocess_layout_paragraphs().
 */
function jcc_tc2_roc_feature_preprocess_layout(array &$variables): void {
  $variables['is_admin'] = \Drupal::service('router.admin_context')->isAdminRoute();
  $variables['is_edit'] = jcc_tc2_roc_feature_is_edit_route();
}

/**
 * Implements hook_preprocess_field().
 */
function jcc_tc2_roc_feature_preprocess_field(array &$variables): void {
  $variables['is_admin'] = \Drupal::service('router.admin_context')->isAdminRoute();
  $variables['is_edit'] = jcc_tc2_roc_feature_is_edit_route();
}

/**
 * Alter the Layout Paragraphs component controls ui.
 *
 * Alter the ui controls (move up / move down / edit / delete / etc.).
 * Implements hook_preprocess_layout_paragraphs_builder_controls().
 *
 * @param array $variables
 *   The variables being passed to the template.
 *
 * @see layout_paragraphs_preprocess_layout_paragraphs_builder_controls()
 */
function jcc_tc2_roc_feature_preprocess_layout_paragraphs_builder_controls(array &$variables): void {
  // Hide duplicate button.
  $variables['duplicate_access'] = FALSE;
  $variables['controls']['duplicate_link']['#access'] = FALSE;
}

/**
 * Prepares variables for views field templates.
 *
 * Default template: views-view-field.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - field: The field handler object for the current field.
 *   - row: Object representing the raw result of the SQL query for the current
 *     field.
 *   - view: Instance of the ViewExecutable object for the parent view.
 */
function jcc_tc2_roc_feature_preprocess_views_view_fields(&$variables): void {
  $view = $variables['view'];
  if ($view->id() == 'jcc_roc_views' && $view->current_display == 'block_1') {

    $rule_document_node = $variables['row']->_entity;
    $media_entities = $rule_document_node->get('field_roc_rule_media')->referencedEntities();
    if (!$media_entities[0]) {
      return;
    }

    $roc_rule_service = \Drupal::service('jcc_tc2_roc_feature.rule.service');
    $file = $roc_rule_service->getMediaDocumentFile($media_entities[0]);

    $mime = $file->getMimeType();
    $mime_type = $mime == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ? t('DOC') : t('PDF');

    $file_url = $file->createFileUrl();
    $url = Url::fromUserInput($file_url);

    $link_to_file = Link::fromTextAndUrl($mime_type, $url)->toString();
    $variables['fields']['field_roc_rule_media']->content = $link_to_file;

    $change_link_url = $rule_document_node->sticky->value;
    if ($change_link_url) {
      $link_title = $rule_document_node->label();
      $new_label_link = Link::fromTextAndUrl($link_title, $url)->toString();
      $variables['fields']['title']->content = $new_label_link;
    }
  }
}

/**
 * Implements hook_preprocess_views_view_table().
 */
function jcc_tc2_roc_feature_preprocess_views_view_table(&$variables) {
  $entity_manager = \Drupal::entityTypeManager();
  $node_manager = $entity_manager->getStorage('node');
  $moderation_information_service = \Drupal::service('content_moderation.moderation_information');

  $view = $variables['view'];
  $display = $view->current_display;
  if ($view->id() == 'jcc_roc_views' && ($display == 'roc_rules_content_admin' || $display == 'roc_rule_index_content_admin')) {

    foreach ($variables['rows'] as $index => $item) {
      // Grab the entity for the row.
      $rule_node = $variables['result'][$index]->_entity;

      // Get all the revision Ids for this entity and get the last revision.
      $revision_ids = $node_manager->revisionIds($rule_node);
      $last_revision_id = end($revision_ids);

      // Load the entity version of the last revision.
      $rule_node_last_revision = $node_manager->loadRevision($last_revision_id);

      // Get the current moderation state of the last revision.
      $state_key = $rule_node_last_revision->get('moderation_state')->value;
      $workflow = $moderation_information_service->getWorkflowForEntity($rule_node_last_revision);
      $label = $workflow->getTypePlugin()->getState($state_key)->label();

      // Set the value of the current moderation state of the latest revision of
      // this rule document.
      $variables['rows'][$index]['columns']['moderation_state']['content'][0]['field_output']['#markup'] = $label;
    }
  }

}
