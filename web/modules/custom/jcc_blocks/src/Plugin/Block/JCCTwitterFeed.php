<?php

namespace Drupal\jcc_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'JCCTwitterFeed' block.
 *
 * @Block(
 *  id = "jcc_twitterfeed",
 *  admin_label = @Translation("JCC Twitter Feed"),
 * )
 */
class JCCTwitterFeed extends BlockBase implements ContainerFactoryPluginInterface {

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
    $form['timeline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Timeline'),
      '#default_value' => $this->configuration['timeline'],
      '#description' => $this->t('Enter the path from the Twitter URL for the timeline that you wish to embed (e.g. /CalCourts or /CalCourts/1322208380912500737?ref_src=twsrc%5Etfw)'),
      '#required' => TRUE,
    ];
    $form['timeline_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Label'),
      '#default_value' => $this->configuration['timeline_label'],
      '#description' => $this->t('Enter the label/text for the link that displays in case the feed does not render. For example, the Twitter default timeline uses "Tweets by CalCourts)'),
      '#required' => TRUE,
    ];

    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Display Options'),
      '#open' => FALSE,
    ];
    $form['options']['opt_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $this->configuration['opt_height'],
      '#description' => $this->t('Adjust height (default: 400)'),
    ];
    $form['options']['opt_header'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide header'),
      '#default_value' => $this->configuration['opt_header'],
    ];
    $form['options']['opt_footer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide footer'),
      '#default_value' => $this->configuration['opt_footer'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $opt_height = ['options', 'opt_height'];
    $opt_header = ['options', 'opt_header'];
    $opt_footer = ['options', 'opt_footer'];

    $this->configuration['timeline'] = $form_state->getValue('timeline');
    $this->configuration['timeline_label'] = $form_state->getValue('timeline_label');
    $this->configuration['opt_height'] = $form_state->getValue($opt_height);
    $this->configuration['opt_header'] = $form_state->getValue($opt_header);
    $this->configuration['opt_footer'] = $form_state->getValue($opt_footer);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $options = [];
    if ($this->configuration['opt_header'] == TRUE) {
      array_push($options, 'noheader');
    }
    if ($this->configuration['opt_footer'] == TRUE) {
      array_push($options, 'nofooter');
    }
    $options = implode(' ', $options);

    $build = [];
    $build['#theme'] = 'jcc_twitterfeed';
    $build['#headergroup'] = ['title' => $this->configuration['label']];
    $build['#twitter'] = [
      'timeline' => $this->configuration['timeline'],
      'timeline_label' => $this->configuration['timeline_label'],
      'height' => $this->configuration['opt_height'],
      'height_default' => 400,
      'options' => $options,
    ];
    $build['#attached'] = ['library' => 'jcc_twitterfeed/twitterfeed_js'];

    return $build;
  }

}
