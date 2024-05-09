<?php

namespace Drupal\jcc_tc2_roc_feature\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\jcc_roc\Service\JccRocRuleService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'JccRocRuleListEmbedBlock' block.
 *
 * @Block(
 *  id = "jcc_roc_rule_list",
 *  admin_label = @Translation("JCC: Rules of Court Listing"),
 * )
 */
class JccRocRuleListEmbedBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The admin context.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The Roc Rules Service.
   *
   * @var \Drupal\jcc_roc\Service\JccRocRuleService
   */
  protected $jccRocRuleService;

  /**
   * Block plugin construct.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   Admin route context service.
   * @param \Drupal\jcc_roc\Service\JccRocRuleService $jcc_roc_rule_service
   *   ROC Rule service.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              AdminContext $admin_context,
                              JccRocRuleService $jcc_roc_rule_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->adminContext = $admin_context;
    $this->jccRocRuleService = $jcc_roc_rule_service;
  }

  /**
   * Block plugin create.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Configuration.
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
      $container->get('router.admin_context'),
      $container->get('jcc_roc.rule.service'),
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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Remove title requirement, then hide title and label display.
    $form['label']['#required'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    if ($this->adminContext->isAdminRoute()) {
      return $this->getAdminBuild();
    }

    $build['roc_list'] = [
      '#type' => 'view',
      '#name' => 'jcc_roc_views',
      '#display_id' => 'block_1',
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdminBuild(): array {
    $build = [];

    $build['label'] = [
      '#prefix' => '<div class="field__label">',
      '#markup' => $this->t('Embed'),
      '#suffix' => '</div>',
    ];

    $build['info'] = [
      '#prefix' => '<div class="field__item"><div class="paragraphs-description paragraphs-collapsed-description"><span class="summary-content">',
      '#markup' => $this->t('Rules of Court: main list'),
      '#suffix' => '</span></div></div>',
    ];

    return $build;
  }

}
