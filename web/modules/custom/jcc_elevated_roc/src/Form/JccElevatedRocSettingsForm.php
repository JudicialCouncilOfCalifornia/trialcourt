<?php

namespace Drupal\jcc_elevated_roc\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * JCC Elevated Sections configuration form.
 */
class JccElevatedRocSettingsForm extends FormBase {

  use StringTranslationTrait;

  /**
   * Entity manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public EntityTypeManagerInterface $entityTypeManager;

  /**
   * The state store.
   *
   * @var Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The messenger interface.
   *
   * @var Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The redirect destination interface.
   *
   * @var Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs the form instance.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StateInterface $state, MessengerInterface $messenger, RedirectDestinationInterface $redirect_destination) {
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
    $this->messenger = $messenger;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('state'),
      $container->get('messenger'),
      $container->get('redirect.destination'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_elevated_roc_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return [
      'roc_defining_category' => $this->state->get('jcc_elevated_roc.roc_defining_category', NULL),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setState($key, $value) {
    $this->state->set('jcc_elevated_roc.' . $key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $state = $this->getState();

    $options = $this->getCategoryList();
    $form['roc_defining_category'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Rules of Court Defining Category ID'),
      '#description' => $this
        ->t('Select the file category that will determine the file media items that are used for the Court Rules section.'),
      '#options' => $options,
      '#default_value' => $state['roc_defining_category'] ?? NULL,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Remove items we don't want to set state for.
    unset($values['submit']);
    unset($values['form_build_id']);
    unset($values['form_token']);
    unset($values['form_id']);
    unset($values['op']);

    foreach ($values as $key => $value) {
      $this->setState($key, $value);
    }

    // Let our guests know that all is updated and well.
    $this->messenger->addMessage(
      $this->t('Settings updated.')
    );
  }

  /**
   * Get list of available vocabularies.
   */
  private function getCategoryList(): array {
    $types = ['' => $this->t('- Select -')];

    $category_source = 'document_type';
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($category_source);
    foreach ($terms as $term) {
      $types[$term->tid] = $term->name;
    }

    return $types;
  }

}
