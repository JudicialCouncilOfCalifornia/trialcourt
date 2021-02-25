<?php

namespace Drupal\jcc_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'JccServices' block.
 *
 * @Block(
 *  id = "jcc_services",
 *  admin_label = @Translation("JCC Services"),
 * )
 */
class JccServices extends BlockBase implements ContainerFactoryPluginInterface {


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

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header Title'),
      '#default_value' => $this->configuration['title'],
      '#weight' => '0',
    ];
    $form['button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button label'),
      '#default_value' => $this->configuration['button_label'],
      '#weight' => '0',
    ];
    $form['link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link'),
      '#default_value' => $this->configuration['link'],
      '#weight' => '0',
    ];

    $form['services'] = [
      '#type' => 'tablefield',
      '#title' => $this->t('Services (Title, Description, Url)'),
      '#default_value' => $this->configuration['services'],
      '#weight' => '0',
      '#cols' => 3,
      '#rows' => count($this->configuration['services']) ?: 1,
      '#addrow' => TRUE,
      '#add_row' => 1,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['link'] = $form_state->getValue('link');
    $this->configuration['button_label'] = $form_state->getValue('button_label');
    $this->configuration['services'] = $form_state->getValue('services')['tablefield']['table'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $service_items = [];
    foreach ($this->configuration['services'] as $service_row) {
      $service_items[] = [
        'title' => $service_row[0],
        'description' => $service_row[1],
        'url' => $service_row[2],
      ];
    }

    $build = [];
    $build['#theme'] = 'jcc_services';
    $build['#jccservices'] = [
      'headergroup' => [
        'title' => $this->configuration['title'],
      ],
      'items' => $service_items,
      'button' => [
        'text' => $this->configuration['title'],
        'url' => $this->configuration['link'],
      ],
    ];

    return $build;
  }

}
