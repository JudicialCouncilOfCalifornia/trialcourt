<?php

namespace Drupal\jcc_block_field_decorator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class JccBlockFieldDecoratorForm.
 */
class JccBlockFieldDecoratorForm extends ConfigFormBase {

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_block_field_decorator_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jcc_block_field_decorator.settings'];
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jcc_block_field_decorator.settings');

    /* @var \Drupal\block_field\BlockFieldManager $block_field */
    $block_field = \Drupal::service('block_field.manager');

    $form['prohibited_blocks'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Prohibited Blocks'),
      '#description' => $this->t('Select blocks that will be removed from the Block dropdown.'),
      '#options' => $block_field->getBlockDefinitionsList(),
      '#default_value' => $config->get('prohibited_blocks'),
      '#weight' => '0',
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('jcc_block_field_decorator.settings')
      ->set('prohibited_blocks', $form_state->getValue('prohibited_blocks'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
