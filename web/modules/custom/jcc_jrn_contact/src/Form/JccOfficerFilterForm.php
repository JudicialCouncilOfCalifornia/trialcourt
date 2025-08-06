<?php

namespace Drupal\jcc_jrn_contact\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Form controller for the jcc officer entity edit forms.
 */
class JccOfficerFilterForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
    RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_jrn_contact_officer_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = $this->requestStack->getCurrentRequest();

    $form['filter'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['form--inline', 'clearfix','officer-container'],
      ],
    ];

    $form['filter']['keyword'] = [
      '#type' => 'textfield',
      '#title' => 'Judicial Officer',
      '#placeholder' => '',
      '#default_value' => $request->get('keyword') ?? '',
    ];

    $terms = $this->entityTypeManager
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

    $location_storage = $this->entityTypeManager->getStorage('jcc_court');
    $location_nodes = $location_storage->loadByProperties(['status' => 1]);
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

    $form['actions']['wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['button-group'],
      ],
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

    foreach (['keyword', 'job_title', 'court'] as $field) {
      $value = $form_state->getValue($field);
      if ($value) {
        $query[$field] = $value;
      }
    }

    $form_state->setRedirect('entity.jcc_officer.collection', $query);
  }

  /**
   * {@inheritdoc}
   */
  public function resetForm(array $form, FormStateInterface &$form_state) {
    $form_state->setRedirect('entity.jcc_officer.collection');
  }

}
