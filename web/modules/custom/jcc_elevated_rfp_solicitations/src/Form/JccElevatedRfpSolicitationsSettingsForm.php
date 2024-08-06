<?php

namespace Drupal\jcc_elevated_rfp_solicitations\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * JCC Elevated Sections configuration form.
 */
class JccElevatedRfpSolicitationsSettingsForm extends FormBase {

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
    return 'jcc_elevated_rfp_solicitations_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return [
      'rfp_media_category_term' => $this->state->get('jcc_elevated.rfp_media_category_term', NULL),
      'rfp_document_type_term' => $this->state->get('jcc_elevated.rfp_document_type_term', NULL),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setState($key, $value) {
    $this->state->set('jcc_elevated.' . $key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $state = $this->getState();
    $rfp_media_category_term = $state['rfp_media_category_term'];
    $rfp_document_type_term = $state['rfp_document_type_term'];

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this
        ->t('Configure settings for the Solicitation Requests (RFPs) of the site.'),
    ];

    $options = $this->getMediaCategoryTermList();
    $form['rfp_media_category_term'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Select default category term.'),
      '#description' => $this
        ->t('Select term to preset the media library filter and preset the document category field on the media item.'),
      '#options' => $options,
      '#default_value' => $state['rfp_media_category_term'] ?? $rfp_media_category_term,
    ];

    $options = $this->getDocumentTypeTermList();
    $form['rfp_document_type_term'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Select default document type.'),
      '#description' => $this
        ->t('Select term to preset the media library filter and preset the document type field on the media item.'),
      '#options' => $options,
      '#default_value' => $state['rfp_document_type_term'] ?? $rfp_document_type_term,
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
   * Get list of available Media Category terms.
   */
  private function getMediaCategoryTermList(): array {

    $vocab_name = 'media_file_category';
    $terms = [];
    $category_terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadTree($vocab_name, 0, 1, TRUE);
    foreach ($category_terms as $term) {
      $terms[$term->id()] = $term->getName();
    }

    return $terms;
  }

  /**
   * Get list of available Document Type terms.
   */
  private function getDocumentTypeTermList(): array {

    $vocab_name = 'document_type';
    $terms = [];
    $type_terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadTree($vocab_name, 0, 1, TRUE);
    foreach ($type_terms as $term) {
      $terms[$term->id()] = $term->getName();
    }

    return $terms;
  }

}
