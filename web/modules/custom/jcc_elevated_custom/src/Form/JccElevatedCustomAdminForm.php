<?php

namespace Drupal\jcc_elevated_custom\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * JCC Elevated Custom configuration form.
 */
class JccElevatedCustomAdminForm extends FormBase {

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
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_elevated_custom_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return [
      'site_machine_name' => $this->state->get('jcc_elevated.site_machine_name'),
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

    $link = Link::createFromRoute('Theme settings', 'system.theme_settings_theme', ['theme' => 'jcc_elevated'])->toString();
    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this
        ->t('Configure general site settings specific for this site. Some settings are set in the @theme_settings_link page.', ['@theme_settings_link' => $link]),
    ];

    $site_machine_name = Settings::get('jcc_elevated.site_machine_name');

    $desc = $site_machine_name ?
      $this->t('The machine name of the site. This is managed in settings.php.') :
      $this->t('The machine name of the site.');

    $form['site_machine_name'] = [
      '#type' => 'textfield',
      '#title' => $this
        ->t('Site machine name'),
      '#description' => $desc,
      '#disabled' => $site_machine_name,
      '#value' => $site_machine_name ?? $state['site_machine_name'],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this
        ->t('Save settings'),
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

    // Define the site name from settings (if available) or from this form.
    $site_machine_name = Settings::get('jcc_elevated.site_machine_name') ?? $values['site_machine_name'];

    // Turn values in to state values.
    $this->setState('site_machine_name', $site_machine_name);

    // Let our guests know that all is updated and well.
    $this->messenger()->addMessage(
      $this->t('Settings updated.')
    );
  }

}
