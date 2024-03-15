<?php

/**
 * @file
 * A file for node add/edit form alters.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Implements hook_form_ENTITY_FORM_alter().
 */
function jcc_elevated_roc_form_node_form_alter(&$form, FormStateInterface $form_state): void {
}

/**
 * Implements hook__field_group_form_process_build_alter().
 */
function jcc_elevated_roc_field_group_form_process_build_alter(array &$element, FormStateInterface $form_state, &$complete_form): void {
  $form_id = $element['#form_id'] ?? '';

  if (in_array($form_id, ['node_document_form', 'node_document_edit_form'])) {
//    $roc_rule_service = \Drupal::service('jcc_elevated_roc.rule.service');
//    $category_id = (string) $roc_rule_service->getRocDefiningDocumentCategoryId();

//    // Disable the chosen library on document type so the states below work.
//    $element['field_document_type']['widget']['#chosen'] = FALSE;
//    $element['field_document_type']['widget']['#multiple'] = FALSE;
//
//    $element['field_document_id']['#states'] = [
//      'visible' => [
//        ':input[name="field_document_type"]' => ['value' => $category_id],
//      ],
//    ];
//
//    $element['field_process_document_trigger']['#states'] = [
//      'visible' => [
//        ':input[name="field_document_type"]' => ['value' => $category_id],
//      ],
//    ];

    // Change the enabled state on processed content fields to make sure it only
    // processes when the rule of court type is set as the type.
//    $states = [
//      'enabled' => [
//        ':input[name="field_document_type"]' => ['value' => $category_id],
//      ],
//    ];
//    $element['field_document_id']['widget']['#states'] = $states;
//    $element['field_processed_document_content']['widget']['#states'] = $states;

    // Change the label of processed document content entity field.
//    $element['field_processed_document_content']['widget']['#title'] = t('Section content');

    // Alter the labels and settings for each processed content entity in the
    // processed document content section.
//    foreach (Element::children($element['field_processed_document_content']['widget']) as $delta => $item) {
//      $section = $element['field_processed_document_content']['widget'][$delta]['inline_entity_form']['#default_value'] ?? FALSE;
//      if ($section && $section->hasField('field_id')) {
//        $id = $section->get('field_id')->first() ? $section->get('field_id')->first()->getValue() : FALSE;
//        if ($id) {
//          $element['field_processed_document_content']['widget'][$delta]['#field_title'] = 'Section: ' . $id['value'];
//        }
//      }
//    }
  }

}
