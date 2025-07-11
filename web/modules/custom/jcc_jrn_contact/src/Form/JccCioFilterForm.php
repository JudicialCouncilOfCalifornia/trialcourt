<?php

namespace Drupal\jcc_jrn_contact\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the jcc staff entity edit forms.
 */
class JccCioFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_jrn_contact_cio_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = \Drupal::request();

    $form['filter'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['form--inline', 'clearfix'],
      ],
    ];

    $form['filter']['first_name'] = [
      '#type' => 'textfield',
      '#title' => 'First Name',
      '#default_value' => $request->get('first_name') ?? '',
    ];

    $form['filter']['last_name'] = [
      '#type' => 'textfield',
      '#title' => 'Last Name',
      '#default_value' => $request->get('last_name') ?? '',
    ];

    $form['filter']['email'] = [
      '#type' => 'textfield',
      '#title' => 'Email',
      '#default_value' => $request->get('email') ?? '',
    ];

    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('job_title');
    $form['filter']['job_title'] = [
      '#type' => 'select',
      '#title' => 'Job Title',
      '#options' => $terms ? array_reduce($terms, function ($carry, $term) {
        $carry[$term->tid] = $term->name;
        return $carry;
      }, []) : [],
      '#empty_option' => 'All Job Titles',
      '#default_value' => $request->get('job_title') ?? '',
    ];

    $location_storage = \Drupal::entityTypeManager()->getStorage('node');
    $location_nodes = $location_storage->loadByProperties(['type' => 'location']);
    $form['filter']['court'] = [
      '#type' => 'select',
      '#title' => 'Court',
      '#options' => $location_nodes ? array_reduce($location_nodes, function ($carry, $node) {
        $carry[$node->id()] = $node->label();
        return $carry;
      }, []) : [],
      '#empty_option' => 'All Courts',
      '#default_value' => $request->get('court') ?? '',
    ];

    $form['actions']['wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-item']],
    ];

    $form['actions']['wrapper']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Filter',
    ];

    if ($request->getQueryString()) {
      $form['actions']['wrapper']['reset'] = [
        '#type' => 'submit',
        '#value' => 'Reset',
        '#submit' => ['::resetForm'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = [];

    foreach (['first_name', 'last_name', 'email', 'job_title', 'court'] as $field) {
      $value = $form_state->getValue($field);
      if ($value) {
        $query[$field] = $value;
      }
    }

    $form_state->setRedirect('entity.jcc_cio.collection', $query);
  }

  /**
   * {@inheritdoc}
   */
  public function resetForm(array $form, FormStateInterface &$form_state) {
    $form_state->setRedirect('entity.jcc_cio.collection');
  }

}
