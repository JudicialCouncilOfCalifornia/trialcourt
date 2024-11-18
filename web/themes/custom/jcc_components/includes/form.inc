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
 * Implements hook_preprocess_input().
 */
function jcc_components_preprocess_input(array &$variables) {
  if (isset($variables['element']['#form_id']) && $variables['element']['#form_id'] == 'openid_connect_login_form') {
    // OpenID's placement setting to show/hide Drupal login context.
    $user_login_display = \Drupal::config('openid_connect.settings')->get('user_login_display');
    if ($user_login_display) {
      $variables['user_login_display'] = $user_login_display;
    }
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
  $options = [];
  if (
    $form['#id'] == 'views-exposed-form-tentative-rulings-tentative-rulings'
    || $form['#id'] == 'views-exposed-form-tentative-rulings-judge-attachment'
    || $form['#id'] == 'views-exposed-form-tentative-rulings-block-1'
  ) {
    $database = \Drupal::database();
    $query = $database->query("SELECT DISTINCT field_judge_target_id FROM node__field_judge WHERE bundle = 'tentative_ruling'");
    $result = $query->fetchAll();
    if (!empty($result)) {
      $fieldJudgeIds = array_column($result, 'field_judge_target_id');
      $nodes = Node::loadMultiple($fieldJudgeIds);
      $options = [];
      foreach ($nodes as $node) {
        $options[$node->id()] = $node->getTitle();
      }
    }

    $form['field_judge_target_id']['#type'] = 'select';
    $form['field_judge_target_id']['#options'] = ['' => 'Select'] + $options;
    $form['field_judge_target_id']['#multiple'] = FALSE;
    $form['field_judge_target_id']['#size'] = 1;

    $form['#validate'][] = 'jcc_components_tentative_rulings_search_validate';

  }

  $view = $form_state->get('view');

  if ($view->id() == 'arbitrators') {
    $termID = $view->args[0];

    if ($termID == 'Civil-ADR-Provider') {
      $form['adr_process']['#states'] = [
        'visible' => [
          ':input[name="arbitrator_type"]' => ['value' => $options['Civil ADR Provider']],
        ],
      ];
      $form['areas_of_law']['#access'] = FALSE;
    }
    elseif ($termID == 'Civil-Early-Settlement-Conference-(CESC)-Neutral') {
      $form['areas_of_law']['#states'] = [
        'visible' => [
          ':input[name="arbitrator_type"]' => ['value' => $options['Civil Early Settlement Conference (CESC) Neutral']],
        ],
      ];
      $form['adr_process']['#access'] = FALSE;
    }
    else {
      $form['adr_process']['#access'] = FALSE;
      $form['areas_of_law']['#access'] = FALSE;
    }
  }

  // Check if this is the specific view exposed form we are targeting.
  if ($form['#id'] == 'views-exposed-form-location-block-1') {
    // Get the count of nodes that have at least one paragraph with "Self-Help".
    $self_help_count =jcc_components_get_self_help_location_count();
    // Update the label of the "Self-Help Locations" filter with the count.
    if (isset($form['field_service_category_target_id'])) {
      $form['field_service_category_target_id']['#prefix'] = '<div class ="self-help-count">';
      $form['field_service_category_target_id']['#suffix'] =  '<span class = "self-help-label">'.' ('.$self_help_count. ')' .'</span>'.'</div>';
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

/**
 * Custom function to get the count of nodes that have paragraphs with "Self-Help".
 *
 * @return int
 *   The count of nodes with at least one "Self-Help" service category.
 */
function jcc_components_get_self_help_location_count() {
  // Query to get the nodes of type "location" with paragraphs that contain the "Self-Help" service category.
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'location')
    ->condition('status', 1) // Ensure the node is published.
    ->exists('field_add_services'); // Ensure the node has the "Add Service" paragraph.

  // Get all node IDs that match this condition.
  $nids = $query->execute();

  // Initialize the count.
  $self_help_count = 0;

  // Load the nodes.
  $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);

  foreach ($nodes as $node) {
    // Get the "Add Service" paragraph field.
    if ($node->hasField('field_add_services') && !$node->get('field_add_services')->isEmpty()) {
      $paragraphs = $node->get('field_add_services')->referencedEntities();

      // Check each paragraph's service category.
      foreach ($paragraphs as $paragraph) {
        if ($paragraph->hasField('field_service_category') && !$paragraph->get('field_service_category')->isEmpty()) {
          $terms = $paragraph->get('field_service_category')->referencedEntities();

          // Check if any term is "Self-Help".
          foreach ($terms as $term) {
            if ($term->getName() == 'Self-Help') {
              $self_help_count++;
              break 2;  // Stop further checks for this node.
            }
          }
        }
      }
    }
  }

  return $self_help_count;
}