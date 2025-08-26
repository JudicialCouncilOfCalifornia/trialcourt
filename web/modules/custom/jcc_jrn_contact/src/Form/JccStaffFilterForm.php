<?php

namespace Drupal\jcc_jrn_contact\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Form controller for the jcc staff entity edit forms.
 */
class JccStaffFilterForm extends FormBase {

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
    return 'jcc_jrn_contact_staff_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = $this->requestStack->getCurrentRequest();

    $form['filter'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['form--inline', 'clearfix'],
      ],
    ];

    $form['filter']['keyword'] = [
      '#type' => 'textfield',
      '#title' => 'Judicial Staff',
      '#placeholder' => '',
      '#default_value' => $request->get('keyword') ?? '',
    ];

    $terms = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadTree('department');
    $form['filter']['department'] = [
      '#type' => 'select',
      '#title' => '',
      '#options' => $terms ? array_reduce($terms, function ($carry, $term) {
        $carry[$term->tid] = $term->name;
        return $carry;
      }, []) : [],
      '#empty_option' => 'All Departments',
      '#default_value' => $request->get('department') ?? '',
    ];

    $form['filter']['temporary'] = [
      '#type' => 'select',
      '#options' => [
        'both' => 'Both',
        1 => 'Yes',
        0  => 'No',
       ],
      '#empty_option' => 'Select ', 
      '#default_value' => $request->query->get('temporary') ?? '',
    ];

    $form['actions']['wrapper'] = [
      '#type' => 'actions',
      '#attributes' => [
        'class' => ['button-group'],
        'style' => 'display: flex; gap: 10px;',
      ],
    ];

    $form['actions']['wrapper']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#submit' => ['::submitForm'],
    ];
    
    if ($request->getQueryString()) {
      $form['actions']['wrapper']['reset'] = [
        '#type' =>'submit',
        '#value' => 'Test',
        '#title' => $this->t('Reset'),
        /*'#url' => Url::fromRoute('entity.jcc_staff.collection'),*/
        '#attributes' => [
          'class' => ['button', 'button--primary', 'button--normal'],
          'role' => 'button',
        ],
      ];   
    }
  return $form;
}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = [];

    foreach (['keyword', 'temporary', 'department'] as $field) {
      $value = $form_state->getValue($field);
      if ($value) {
        $query[$field] = $value;
      }
    }

    $form_state->setRedirect('entity.jcc_staff.collection', [],['query' => $query]);
  }

  /**
   * {@inheritdoc}
   */
  public function resetForm(array $form, FormStateInterface &$form_state) {
    $form_state->setRedirect('entity.jcc_staff.collection',[], ['query' => []]);
    $form_state->setUserInput([]);
  }

}
