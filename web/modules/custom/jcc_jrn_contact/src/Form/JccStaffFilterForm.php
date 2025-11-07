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
        'class' => ['form--inline', 'clearfix', 'views-exposed-form'],
      ],
    ];

    $form['filter']['keyword'] = [
      '#type' => 'textfield',
      '#title_display' => 'after',
      '#placeholder' => 'Enter a Name',
      '#default_value' => $request->query->get('keyword') ?? '',
      '#attributes' => [
        'class' => ['form-text'],
        'size' => 30,
        'maxlength' => 128,
        'data-drupal-selector' => 'edit-combine',
        'id' => 'edit-combine--6',
      ],
      '#wrapper_attributes' => [
        'class' => [
          'placeholder-container',
          'form-item',
        ],
      ],
      '#prefix' => '<div class="placeholder-container form-item "> 
                    <h2 class="filter-search-heading">Search</h2>
                    <div>Enter <b>Judicial Council Staff</b> name</div>',
      '#suffix' => '</div>',
    ];
    $form['filter']['buttons'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['button-group'],
        'style' => 'gap: 15px;',
      ],
    ];

    $form['filter']['buttons']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
      '#submit' => ['::submitForm'],
      '#attributes' => [
        'class' => ['button', 'button--secondary', 'button--normal'],
      ],
    ];

    if ($request->getQueryString()) {
      $form['filter']['buttons']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#submit' => ['::resetForm'],
        '#attributes' => [
          'class' => ['button', 'button--primary', 'button--normal'],
        ],
      ];
    }
    $terms = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadTree('department');
    $form['filter']['department'] = [
      '#type' => 'select',
      '#title_display' => 'after',
      '#options' => $terms ? array_reduce($terms, function ($carry, $term) {
        $carry[$term->tid] = $term->name;
        return $carry;
      }, []) : [],
      '#empty_option' => 'All Departments',
      '#default_value' => $request->get('department') ?? '',
      '#attributes' => [
        'class' => [
          'form-select',
        ],
      ],
      '#wrapper_attributes' => [
        'class' => [
          'placeholder-container',
          'form-item',
        ],
      ],
      '#prefix' => '<div class="placeholder-container form-item" >
                    <br />
                    <h2 class="filter-search-heading">Filter By</h2><div>Department</div>',
      '#suffix' => '</div>',
    ];

    $form['filter']['department']['#attributes']['onchange'] = 'this.form.submit()';
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

    $form_state->setRedirect('entity.jcc_staff.collection', [], ['query' => $query]);
  }

  /**
   * {@inheritdoc}
   */
  public function resetForm(array $form, FormStateInterface &$form_state) {
    $form_state->setRedirect('entity.jcc_staff.collection', [], ['query' => []]);
    $form_state->setUserInput([]);
  }

}
