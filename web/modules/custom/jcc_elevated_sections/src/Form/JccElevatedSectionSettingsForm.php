<?php

namespace Drupal\jcc_elevated_sections\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\jcc_elevated_sections\Constants\JccSectionConstants;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * JCC Elevated Sections configuration form.
 */
class JccElevatedSectionSettingsForm extends FormBase {

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
    return 'jcc_elevated_sections_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return [
      'section_vocab_source' => $this->state->get('jcc_elevated.section_vocab_source', JccSectionConstants::JCC_SECTIONS_DEFAULT_SOURCE_ID),
      'section_allowed_types' => $this->state->get('jcc_elevated.section_allowed_types', []),
      'section_url_prefix_types' => $this->state->get('jcc_elevated.section_url_prefix_types', []),
      'section_allowed_media_types' => $this->state->get('jcc_elevated.section_allowed_media_types', []),
      'section_applied_views' => $this->state->get('jcc_elevated.section_applied_views', []),
      'section_applied_views_general_content_excluded' => $this->state->get('jcc_elevated.section_applied_views_general_content_excluded', []),
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
    $section_vocab_source = $state['section_vocab_source'];

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this
        ->t('Configure settings for the grouping/sectioning of the site.'),
    ];

    $options = $this->getVocabularyList();
    $form['section_vocab_source'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Select source'),
      '#description' => $this
        ->t('Select the taxonomy vocabulary that will be the source of section data.'),
      '#options' => $options,
      '#default_value' => $state['section_vocab_source'] ?? $section_vocab_source,
    ];

    if ($state['section_vocab_source']) {
      $route_parameters = ['taxonomy_vocabulary' => $state['section_vocab_source']];
      $url_options = [
        'query' => $this->redirectDestination->getAsArray(),
        'attributes' => [
          'class' => 'button button--secondary',
        ],
      ];
      $form['link_to_vocab'] = [
        '#type' => 'link',
        '#title' => $this
          ->t('Manage the @name taxonomy', ['@name' => $options[$state['section_vocab_source']]]),
        '#url' => Url::fromRoute('entity.taxonomy_vocabulary.overview_form', $route_parameters, $url_options),
      ];
    }

    $form['section_type_group'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('Content types'),
      '#description' => $this
        ->t('Select views to apply section contextual filtering, or add a filter to the exposed form filter (if it exists).'),
      '#open' => TRUE,
    ];

    $form['section_type_group']['section_allowed_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this
        ->t('Select content types'),
      '#description' => $this
        ->t('Select the content types that may be sectioned.'),
      '#options' => $this
        ->getContentTypesList(),
      '#default_value' => $state['section_allowed_types'] ?? [],
    ];

    $form['section_type_group']['section_url_prefix_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this
        ->t('Select types for URL prefix'),
      '#description' => $this
        ->t('Select the content types that should have the URL prefix applied to it. <strong>NOTE: Types NOT checked here will still use a modified URL pattern applied to them as they will still need to use the URL pattern of any parent menu items.</strong>'),
      '#options' => $this
        ->getContentTypesList(),
      '#default_value' => $state['section_url_prefix_types'] ?? [],
    ];

    $form['section_media_type_group'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('Media types'),
      '#description' => $this
        ->t('Select views to apply section contextual filtering, or add a filter to the exposed form filter (if it exists).'),
      '#open' => TRUE,
    ];

    $form['section_media_type_group']['section_allowed_media_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this
        ->t('Select media types'),
      '#description' => $this
        ->t('Select the media types that may be sectioned.'),
      '#options' => $this
        ->getMediaTypesList(),
      '#default_value' => $state['section_allowed_media_types'] ?? [],
    ];

    $form['section_views'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('Views filtering'),
      '#description' => $this
        ->t('Select views to apply section contextual filtering, or add a filter to the exposed form filter (if it exists).'),
      '#open' => TRUE,
    ];

    $form['section_views']['section_applied_views'] = [
      '#type' => 'checkboxes',
      '#title' => $this
        ->t('Select views'),
      '#description' => $this
        ->t('Select the view:display combo that you would like to auto apply the section filtering.'),
      '#options' => $this
        ->getViewDisplayList(),
      '#default_value' => $state['section_applied_views'] ?? [],
    ];

    $form['section_views']['section_applied_views_general_content_excluded'] = [
      '#type' => 'checkboxes',
      '#title' => $this
        ->t('General content excluded'),
      '#description' => $this
        ->t('If if view is set to be sectioned, the default behavior is to include general/non-sectioned content in returned results. Select to view here to change the default behavior and exclude non-sectioned content.'),
      '#options' => $this
        ->getViewDisplayList(),
      '#default_value' => $state['section_applied_views_general_content_excluded'] ?? [],
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
   * Get list of available content types.
   */
  private function getContentTypesList(): array {
    $types = [];
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($content_types as $type) {
      $types[$type->id()] = $type->label();
    }

    return $types;
  }

  /**
   * Get list of available content types.
   */
  private function getMediaTypesList(): array {
    $types = [];
    $media_types = $this->entityTypeManager->getStorage('media_type')->loadMultiple();
    foreach ($media_types as $type) {
      $types[$type->id()] = $type->label();
    }

    return $types;
  }

  /**
   * Get list of available vocabularies.
   */
  private function getVocabularyList(): array {
    $types = [];
    $vocabs = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
    foreach ($vocabs as $type) {
      $types[$type->id()] = $type->label();
    }

    return $types;
  }

  /**
   * Get list of available vocabularies.
   */
  private function getViewDisplayList(): array {
    $views = Views::getViewsAsOptions();

    // Remove views that shouldn't even be considered, like disabled, or some
    // admin views.
    $items = [];
    $views_to_remove = [
      'archive',
      'block_content',
      'content_recent',
      'export_feedback',
      'event_type_legend',
      'feeds_directory',
      'files',
      'forms',
      'frontpage',
      'glossary',
      'glossary_terms',
      'group_overview',
      'imported_events',
      'jcc_migrate_source_ui',
      'media_entity_browser',
      'moderated_content',
      'news_types',
      'nodeaccess',
      'revisions',
      'scheduler_scheduled_content',
      'taxonomy_term',
      'tmgmt_job_overview',
      'tmgmt_job_items',
      'tmgmt_job_messages',
      'tmgmt_translation_all_job_items',
      'users_by_group',
      'watchdog',
      'webform_submissions',
      'who_s_new',
      'who_s_online',
    ];

    foreach ($views as $id => $view) {
      $args = $view->getArguments();
      $view_name = $args['@view'];
      $display_name = $args['@display'];

      if (!in_array($view_name, $views_to_remove)) {
        if ($display_name != 'default') {
          $items[$id] = $view;
        }
      }
    }

    return $items;
  }

}
