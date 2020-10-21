<?php

namespace Drupal\jcc_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'JccSwitchboard' block.
 *
 * @Block(
 *  id = "jcc_switchboard",
 *  admin_label = @Translation("JCC Switchboard"),
 * )
 */
class JccSwitchboard extends BlockBase implements ContainerFactoryPluginInterface {


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

    $form['brow'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Brow'),
      '#default_value' => $this->configuration['brow'],
      '#weight' => '0',
    ];
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header Title'),
      '#default_value' => $this->configuration['title'],
      '#weight' => '0',
    ];
    $form['primary'] = [
      '#type' => 'tablefield',
      '#title' => $this->t('Items (brow, title, url)'),
      '#default_value' => $this->configuration['primary'],
      '#weight' => '0',
      '#cols' => 3,
      '#rows' => count($this->configuration['primary']) ?: 1,
      '#addrow' => TRUE,
      '#add_row' => 1,
    ];
//    $form['secondary'] = [
//      '#type' => 'tablefield',
//      '#title' => $this->t('Secondary Items (brow, title, url)'),
//      '#default_value' => $this->configuration['secondary'],
//      '#weight' => '0',
//      '#cols' => 3,
//      '#rows' => count($this->configuration['secondary']) ?: 1,
//      '#addrow' => TRUE,
//      '#add_row' => 1,
//    ];

    $form['dark_bg'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dark Background'),
      '#default_value' => $this->configuration['dark_bg'],
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['brow'] = $form_state->getValue('brow');
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['primary'] = $form_state->getValue('primary')['tablefield']['table'];
//    $this->configuration['secondary'] = $form_state->getValue('secondary')['tablefield']['table'];
    $this->configuration['dark_bg'] = $form_state->getValue('dark_bg');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $primary_items = $secondary_items = [];
    foreach ($this->configuration['primary'] as $primary_row) {
      $primary_items[] = [
        'brow' => $primary_row[0],
        'title' => $primary_row[1],
        'url' => $primary_row[2],
      ];
    }
//    foreach ($this->configuration['secondary'] as $secondary_row) {
//      $secondary_items[] = [
//        'brow' => $secondary_row[0],
//        'title' => $secondary_row[1],
//        'url' => $secondary_row[2],
//      ];
//    }

    $build = [];
    $build['#theme'] = 'jcc_switchboard';
    $build['#switchboard'] = [
      'layout_variant' => $this->configuration['dark_bg'] ? 'default jcc-switchboard--related-content' : '',
      'background_variant' => $this->configuration['dark_bg'] ? 'has-background-color--dark--primary' : '',
      'headergroup' => [
        'brow' => $this->configuration['brow'],
        'title' => $this->configuration['title'],
      ],
      'items' => [
        'primary' => $primary_items,
//        'secondary' => $secondary_items,
      ],
    ];

    return $build;
  }



}
