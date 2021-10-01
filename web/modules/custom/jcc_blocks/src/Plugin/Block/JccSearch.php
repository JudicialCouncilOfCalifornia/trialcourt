<?php

namespace Drupal\jcc_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'JccSearch' block.
 *
 * @Block(
 *  id = "jcc_search",
 *  admin_label = @Translation("JCC Search"),
 * )
 */
class JccSearch extends BlockBase implements ContainerFactoryPluginInterface {


  /**
   * Account interface.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Block plugin construct.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   Module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $account, ModuleHandler $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Block plugin create.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;

    $form['form_action'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form Action'),
      '#default_value' => $this->configuration['form_action'],
      '#weight' => '0',
    ];
    $form['filter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter'),
      '#default_value' => $this->configuration['filter'],
      '#weight' => '0',
    ];
    $form['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->configuration['placeholder'],
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['form_action'] = $form_state->getValue('form_action');
    $this->configuration['filter'] = $form_state->getValue('filter');
    $this->configuration['placeholder'] = $form_state->getValue('placeholder');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'jcc_search';
    $build['#search'] = [
      'form_action' => $this->configuration['form_action'],
      'search_input' => [
        'form_input_name' => "keywords",
        'placeholder' => $this->configuration['placeholder'],
      ],
      'filter' => $this->configuration['filter'],
    ];

    return $build;
  }

}
