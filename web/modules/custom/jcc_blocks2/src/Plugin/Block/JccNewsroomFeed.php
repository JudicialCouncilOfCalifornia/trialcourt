<?php

namespace Drupal\jcc_blocks2\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'JCCNewsroomFeed' block.
 *
 * @Block(
 *  id = "jcc_newsroomfeed",
 *  admin_label = @Translation("JCC Newsroom Feed"),
 * )
 */
class JccNewsroomFeed extends BlockBase implements ContainerFactoryPluginInterface {

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
    $form['topic_ids'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Topic IDs'),
      '#default_value' => $this->configuration['topic_ids'],
      '#description' => $this->t('Generate a feed from https://newsroom.courts.ca.gov/embed/generate. Enter the id numbers for the selected topics shown in the feed generator. If entering more than one, combine the ids with a plus (e.g. 34+121)'),
    ];

    $form['max_articles'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max Articles'),
      '#default_value' => $this->configuration['max_articles'],
      '#description' => $this->t('Adjust the number of articles to display between 1 and 100. Default is 10 if left blank.'),
    ];

    $form['visibility_options'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#attributes' => [
        'class' => 'form-item__label',
      ],
      '#value' => 'Visibility Options',
    ];

    $form['hide_thumbnail'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide Thumbnail'),
      '#default_value' => $this->configuration['hide_thumbnail'],
    ];

    $form['hide_publish_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide Publish Date'),
      '#default_value' => $this->configuration['hide_publish_date'],
    ];

    $form['hide_description'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide Description'),
      '#default_value' => $this->configuration['hide_description'],
    ];

    $form['feed_width'] = [
      '#type' => 'select',
      '#title' => $this->t('Select size'),
      '#options' => [
        150 => $this->t('150'),
        300 => $this->t('300'),
        600 => $this->t('600'),
      ],
      '#default_value' => $this->configuration['feed_width'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['topic_ids'] = $form_state->getValue('topic_ids');
    $this->configuration['max_articles'] = $form_state->getValue('max_articles');
    $this->configuration['hide_thumbnail'] = $form_state->getValue('hide_thumbnail');
    $this->configuration['hide_publish_date'] = $form_state->getValue('hide_publish_date');
    $this->configuration['hide_description'] = $form_state->getValue('hide_description');
    $this->configuration['feed_width'] = $form_state->getValue('feed_width');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $hide_thumbnail = 'no';
    if ($this->configuration['hide_thumbnail'] == TRUE) {
      $hide_thumbnail = 'yes';
    }
    $hide_publish_date = 'no';
    if ($this->configuration['hide_publish_date'] == TRUE) {
      $hide_publish_date = 'yes';
    }
    $hide_description = 'no';
    if ($this->configuration['hide_description'] == TRUE) {
      $hide_description = 'yes';
    }

    $build = [];
    $build['#theme'] = 'jcc_newsroomfeed';
    $build['#title'] = ['title' => $this->configuration['label']];
    $build['#feed'] = [
      'topic_ids' => $this->configuration['topic_ids'],
      'max_articles' => $this->configuration['max_articles'],
      'hide_thumbnail' => $hide_thumbnail,
      'hide_publish_date' => $hide_publish_date,
      'hide_description' => $hide_description,
      'feed_width' => $this->configuration['feed_width'],
    ];

    return $build;
  }

}
