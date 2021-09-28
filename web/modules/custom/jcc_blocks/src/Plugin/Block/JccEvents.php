<?php

namespace Drupal\jcc_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'JccEvents' block.
 *
 * @Block(
 *  id = "jcc_events",
 *  admin_label = @Translation("JCC Events"),
 * )
 */
class JccEvents extends BlockBase implements ContainerFactoryPluginInterface {


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

    $form['first_column'] = [
      '#type' => 'textarea',
      '#title' => $this->t('First Column'),
      '#default_value' => $this->configuration['first_column'],
      '#weight' => '0',
    ];
    $form['second_column'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Second Column'),
      '#default_value' => $this->configuration['second_column'],
      '#weight' => '0',
    ];
    $form['third_column'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Third Column'),
      '#default_value' => $this->configuration['third_column'],
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['first_column'] = $form_state->getValue('first_column');
    $this->configuration['second_column'] = $form_state->getValue('second_column');
    $this->configuration['third_column'] = $form_state->getValue('third_column');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'jcc_events';
    $build['#events'] = [
      'first_column' => $this->configuration['first_column'],
      'second_column' => $this->configuration['second_column'],
      'third_column' => $this->configuration['third_column'],
    ];

    return $build;
  }

}
