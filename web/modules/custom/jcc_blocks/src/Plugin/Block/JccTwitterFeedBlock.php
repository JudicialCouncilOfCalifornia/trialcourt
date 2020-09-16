<?php

namespace Drupal\jcc_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Twitter Feed' Block.
 *
 * @Block(
 *   id = "twitter_feed",
 *   admin_label = @Translation("Twitter Feed"),
 *   category = @Translation("Social Media"),
 * )
 */
class JccTwitterFeedBlock extends BlockBase implements ContainerFactoryPluginInterface {
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
    $form['feed_embed_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter Feed Embed URL'),
      '#default_value' => $this->configuration['feed_embed_url'],
      '#maxlength' => 64,
      '#size' => 64,
      '#description' => $this->t('Enter the URL for the twitter feed embed (e.g. https://twitter.com/CalCourts?ref_src=twsrc%5Etfw).'),
      '#weight' => '1',
      '#required' => TRUE,
    ];

    $form['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display Width'),
      '#maxlength' => 3,
      '#size' => 3,
      '#weight' => '1',
    ];

    $form['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display Height'),
      '#maxlength' => 3,
      '#size' => 3,
      '#weight' => '1',
    ];

    $form['embed_parameters'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Optional Embed Parameters'),
      '#default_value' => $this->configuration['embed_parameters'],
      '#description' => $this->t('Reference https://developer.twitter.com/en/docs/twitter-for-websites/timelines/guides/parameter-reference.'),
      '#weight' => '1',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['feed_embed_url'] = $form_state->getValue('feed_embed_url');
    $this->configuration['width'] = $form_state->getValue('width');
    $this->configuration['height'] = $form_state->getValue('height');
    $this->configuration['embed_parameters'] = $form_state->getValue('embed_parameters');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'jcc_twitter_feed_block';
    $build['#twitter_settings'] = [
      'feed_embed_url' => $this->configuration['feed_embed_url'],
      'height' => $this->configuration['height'],
      'width' => $this->configuration['width'],
      'embed_parameters' => $this->configuration['embed_parameters'],
    ];

    return $build;
  }

}
