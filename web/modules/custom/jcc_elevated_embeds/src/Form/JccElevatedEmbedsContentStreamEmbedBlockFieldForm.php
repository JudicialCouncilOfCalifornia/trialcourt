<?php

namespace Drupal\jcc_elevated_embeds\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class JccElevatedCustomContentStreamEmbedBlockFieldForm.
 */
class JccElevatedEmbedsContentStreamEmbedBlockFieldForm extends FormBase {

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
   * Creates a MyForm instance.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StateInterface $state, MessengerInterface $messenger) {
    $this->entity_type_manager = $entity_type_manager;
    $this->state = $state;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('state'),
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_elevated_embeds_content_stream_embed_block_field_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return [
      'allowed_blocks' => $this->state->get('jcc_elevated_embeds.allowed_blocks'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setState($key, $value) {
    $this->state->set('jcc_elevated_embeds.' . $key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $state = $this->getState();

    $blockManager = \Drupal::service('plugin.manager.block');
    $contextRepository = \Drupal::service('context.repository');
    $definitions = $blockManager->getDefinitionsForContexts($contextRepository->getAvailableContexts());

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this
        ->t('Determine the Blocks/Apps that can be embedded in a Content Stream/Embed paragraph. This can be set per site without having to manage any configuration.'),
    ];

    $options = [];
    foreach ($definitions as $plugin_id => $definition) {
      $options[$plugin_id] = [
        ['label' => $definition['admin_label']],
        ['id' => $plugin_id],
        ['category' => (string) $definition['category']],
        ['provider' => $definition['provider']],
      ];
    }

    $default_value = !empty($state['allowed_blocks']) ? $state['allowed_blocks'] : array_keys($options);
    $default = array_combine($default_value, $default_value);

    $form['allowed_blocks'] = [
      '#type' => 'tableselect',
      '#header' => [
        $this->t('Label'),
        $this->t('ID'),
        $this->t('Category'),
        $this->t('Provider'),
      ],
      '#options' => $options,
      '#js_select' => FALSE,
      '#multiple' => TRUE,
      '#required' => FALSE,
      '#empty' => $this->t('No blocks are available.'),
      '#default_value' => $default,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->setState('allowed_blocks', $values['allowed_blocks']);

    // Let our guests know that all is updated and well.
    $this->messenger()->addMessage(
      $this->t('Settings updated.')
    );
  }

}
