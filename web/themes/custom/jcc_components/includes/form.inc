<?php

/**
 * @file
 * Provides hooks for jcc_components module.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\node\Entity\Node;

/**
 * Implements hook_theme_suggestions_alter().
 */
function jcc_components_theme_suggestions_alter(array &$suggestions, array $variables, $hook) {
  if (
    isset($variables['element']['#form_id'])
    && isset($variables['element']['#type'])
    && isset($variables['element']['#name'])
  ) {
    $element = $variables['element'];
    $formid = str_replace('-', '_', $element['#form_id']);
    $suggestions[] = $hook . '__' . $formid;
    $suggestions[] = $hook . '__' . $element['#type'] . '__' . $formid;
    $suggestions[] = $hook . '__' . $element['#name'] . '__' . $formid;
    $suggestions[] = $hook . '__' . $element['#name'] . '__' . $element['#type'] . '__' . $formid;
  }
}

/**
 * Implements hook_form_alter().
 */
function jcc_components_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  _jcc_components_attach_form_id($form, $form_id);
  switch ($form_id) {
    case 'node_tentative_ruling_form':
    case 'node_tentative_ruling_edit_form':
      // Department field is used on Judge content type with multiple values.
      // Only allow one value to be selected on tentative ruling.
      $form['field_department']['widget']['#multiple'] = FALSE;
      $options = $form['field_department']['widget']['#options'];
      $options = ['_none' => t('- Select a value -')] + $options;
      $form['field_department']['widget']['#options'] = $options;
      break;
  }
}

/**
 * Implements hook_preprocess_form_element().
 */
function jcc_components_preprocess_form_element(array &$variables) {
  if (isset($variables['label']) && isset($variables['element']['#form_id'])) {
    $variables['label']['#form_id'] = $variables['element']['#form_id'];
  }
  if (isset($variables['label']) && isset($variables['element']['#type'])) {
    $variables['label']['#form_element_type'] = $variables['element']['#type'];
  }
}

/**
 * Attaches form id to all form elements.
 *
 * @param array $form
 *   The form or form element which children should have form id attached.
 * @param string $form_id
 *   The form id attached to form elements.
 */
function _jcc_components_attach_form_id(array &$form, $form_id) {
  foreach (Element::children($form) as $child) {
    if (!isset($form[$child]['#form_id'])) {
      $form[$child]['#form_id'] = $form_id;
    }
    _jcc_components_attach_form_id($form[$child], $form_id);
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function jcc_components_form_views_exposed_form_alter(&$form, FormStateInterface $form_state) {
  $options=[];
  if (
    $form['#id'] == 'views-exposed-form-tentative-rulings-tentative-rulings'
    || $form['#id'] == 'views-exposed-form-tentative-rulings-judge-attachment'
    || $form['#id'] == 'views-exposed-form-tentative-rulings-block-1'
  ) {
    $nids = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'judge')
      ->sort('title', 'ASC')
      ->execute();

    $nodes = Node::loadMultiple($nids);
    foreach ($nodes as $node) {
      $options[$node->id()] = $node->getTitle();
    }

    

  }

  $view = $form_state->get('view');

  if ($view->id() == 'arbitrators') {
 $termID=$view->args[0];
 $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($termID);
 if($term){
  $term_name=$term->getName();
 }

    if ($termID == 'Civil-ADR-Provider') {
     
      $form['adr_process']['#states'] = [
        'visible' => [
          ':input[name="arbitrator_type"]' => ['value' => $options['Civil ADR Provider']],
        ],
      ];
      $form['areas_of_law']['#access'] =false; 
    } elseif ($termID == 'Civil-Early-Settlement-Conference-(CESC)-Neutral') {
      
      $form['areas_of_law']['#states'] = [
        'visible' => [
          ':input[name="arbitrator_type"]' => ['value' => $options['Civil Early Settlement Conference (CESC) Neutral']],
        ],
      ];
      $form['adr_process']['#access'] =false;; 
    } else {
      $form['adr_process']['#access'] =false;
      $form['areas_of_law']['#access'] =false;
    }
  }
}


/**
 * Validate search parameters for tentative rulings.
 *
 * @param array $form
 *   The form array.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   Form state object.
 */
function jcc_components_tentative_rulings_search_validate(array &$form, FormStateInterface $form_state) {
  $values = $form_state->getValues();
  if (empty($values['case_number']) && empty($values['field_judge_target_id']) && ($form['#id'] != 'views-exposed-form-tentative-rulings-block-1')) {
    $form_state->setErrorByName('field_judge_target_id', t('Enter a case number or select a Judicial Officer.'));
  }

}
