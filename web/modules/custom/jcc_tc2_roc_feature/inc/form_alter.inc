<?php

/**
 * @file
 * Contains jcc_tc2_roc_feature form_alter module functionality.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_alter().
 */
function jcc_tc2_roc_feature_form_alter(&$form, &$form_state, $form_id): void {

  if ($form_id == 'layout_paragraphs_component_form') {
    // This is for the paragraph components form.
  }

  $form_names = [
    'node_roc_rule_index_form',
    'node_roc_rule_index_edit_form',
  ];

  if (in_array($form_id, $form_names)) {
    $form['field_roc_rule_index_sections']['widget']['header_actions']['dropdown_actions']['dragdrop_mode']['#access'] = FALSE;
    $form['field_roc_rule_index_sections']['widget']['header_actions']['dropdown_actions']['edit_all']['#access'] = FALSE;
    $form['field_roc_rule_index_sections']['widget']['header_actions']['actions']['collapse_all']['#access'] = FALSE;
    $form['field_roc_rule_index_sections']['widget']['add_more']['add_more_button_rule_section']['#value'] = t('Add @type', ['@type' => 'a new rule section grouping']);

    $form['field_roc_rule_index_sections']['widget']['#title'] = '';

    $promote_title = t('Display in Rules of Court');
    $form['promote']['widget']['#title'] = $promote_title;
    $form['promote']['widget']['value']['#title'] = $promote_title;

    $promote_description = t('Display this rule document in the main listing of Rules of Court.');
    $form['promote']['widget']['#description'] = $promote_description;
    $form['promote']['widget']['value']['#description'] = $promote_description;

    $sticky_title = t('Link to file in main index');
    $form['sticky']['widget']['#title'] = $sticky_title;
    $form['sticky']['widget']['value']['#title'] = $sticky_title;

    $sticky_description = t('On the main listing, set the link to the first attached file instead of this node.');
    $form['sticky']['widget']['#description'] = $sticky_description;
    $form['sticky']['widget']['value']['#description'] = $sticky_description;
  }
}

/**
 * Implements hook_field_widget_WIDGET_TYPE_form_alter().
 */
function jcc_tc2_roc_feature_field_widget_form_alter(&$element, &$form_state, $context): void {

  $widget_types = [
    'paragraphs',
    'bert',
  ];

  if (in_array($context['widget']->getPluginId(), $widget_types)) {
    $name = $context['items']->getFieldDefinition()->getName();

    if ($name == 'field_roc_rule_index_sections') {
      $element['subform']['field_rule_index_section_label']['widget'][0]['value']['#title'] = FALSE;
      $element['subform']['field_rule_index_section_content']['widget'][0]['value']['#title_display'] = 'hidden';
      $element['subform']['field_rule_index_section_label']['widget'][0]['value']['#title_display'] = 'hidden';
    }

    if ($name == 'field_rule_index_section_label') {
      $element['subform']['field_rule_index_section_label']['widget'][0]['value']['#title'] = FALSE;
      $element['subform']['field_rule_index_section_content']['widget'][0]['value']['#title_display'] = 'hidden';
      $element['subform']['field_rule_index_section_label']['widget'][0]['value']['#title_display'] = 'hidden';
    }

    if ($name == 'field_rule_index_section_content') {
      // Add the nids of subsections that do not contain the parent document id.
      // This will limit the returned list to only subsections already marked
      // for the document we are working on.
      $rule_section_paragraph_entity = $context['items']->getEntity();
      $parent_rule_document = $rule_section_paragraph_entity->getParentEntity() ?? FALSE;
      $parent_rule_document_id = $parent_rule_document ? $parent_rule_document->id() : FALSE;
      if ($parent_rule_document_id) {
        $ignored_entities = $element['add']['entity']['#selection_settings']['ignored_entities'];
        $additional_subsections_to_ignore = jcc_tc2_roc_feature_get_subsection_ids_not_with_document_id($parent_rule_document_id);
        $new_ignored_entities = array_merge($ignored_entities, $additional_subsections_to_ignore);
        $element['add']['entity']['#selection_settings']['ignored_entities'] = $new_ignored_entities;
      }
    }
  }
}

/**
 * Get rules that do not have the rule index id assigned to them.
 */
function jcc_tc2_roc_feature_get_subsection_ids_not_with_document_id($parent_rule_document_id): array {
  if (!$parent_rule_document_id) {
    return [];
  }

  $results = \Drupal::entityQuery('node')
    ->condition('type', 'roc_rule')
    ->condition('field_roc_rule_parent_index', $parent_rule_document_id, '!=')
    ->sort('title', 'ASC')
    ->execute();

  return array_values($results);
}

/**
 * Alter the Layout Paragraph component form.
 *
 * Implements hook_form_FORM_ID_alter().
 *
 * @param array $form
 *   The Layout Paragraph component form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The Layout Paragraph Component Form State.
 *
 * @see layout_paragraphs_form_layout_paragraphs_component_form_alter()
 * */
function jcc_tc2_roc_feature_form_layout_paragraphs_component_form_alter(array &$form, FormStateInterface &$form_state): void {
  // Make custom alterations to adjust the Layout Paragraph Component Form.
  $form['captcha']['#access'] = FALSE;
  $form['layout_paragraphs']['#access'] = FALSE;
}

/**
 * Alter the Layout Paragraph component form.
 *
 * Implements hook_layout_paragraph_element_form_alter().
 *
 * @param array $form
 *   The Layout Paragraph component form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The Layout Paragraph Component Form State.
 *
 * @see layout_paragraphs_form_layout_paragraphs_component_form_alter()
 * */
function jcc_tc2_roc_feature_layout_paragraph_element_form_alter(array &$form, FormStateInterface &$form_state): void {
  // Make custom alterations to adjust the Layout Paragraph Component Form.
  $form['captcha']['#access'] = FALSE;
  $form['layout_paragraphs']['#access'] = FALSE;
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function jcc_tc2_roc_feature_form_views_exposed_form_alter(&$form, &$form_state): void {
  $view = $form_state->getStorage('view');
  if ($view['view']->id() == 'jcc_roc_views' && $view['view']->current_display == 'roc_rules_content_admin') {

    // Convert parent document filter to dropdown select.
    $form['field_roc_rule_parent_index_target_id']['#type'] = 'select';
    unset($form['field_roc_rule_parent_index_target_id']['#size']);

    $roc_rule_service = \Drupal::service('jcc_tc2_roc_feature.rule.service');
    $rule_indexes = $roc_rule_service->getRocAllRuleIndexes();
    $options = ['' => '- Any -'];
    foreach ($rule_indexes as $id => $rule_index) {
      $options[$id] = $rule_index->label();
    }
    $form['field_roc_rule_parent_index_target_id']['#options'] = $options;
  }

}