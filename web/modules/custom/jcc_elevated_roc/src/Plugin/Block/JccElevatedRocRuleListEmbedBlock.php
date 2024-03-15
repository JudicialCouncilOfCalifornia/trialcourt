<?php

namespace Drupal\jcc_elevated_roc\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\jcc_elevated_roc\Service\JccElevatedRocRuleListService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'JccElevatedRocRuleListEmbedBlock' block.
 *
 * @Block(
 *  id = "jcc_elevated_roc_rule_list",
 *  admin_label = @Translation("JCC Elevated: Rules of Court Listing"),
 * )
 */
class JccElevatedRocRuleListEmbedBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The admin context.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The Roc Rules List Service.
   *
   * @var \Drupal\jcc_elevated_roc\Service\JccElevatedRocRuleListService
   */
  protected $jccElevatedRocRuleList;

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
   * @param \Drupal\jcc_elevated_roc\Service\JccElevatedRocRuleListService $jcc_elevated_roc_rule_list
   *   Elevated ROC Rule list service.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              AdminContext $admin_context,
                              JccElevatedRocRuleListService $jcc_elevated_roc_rule_list) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->adminContext = $admin_context;
    $this->jccElevatedRocRuleList = $jcc_elevated_roc_rule_list;
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
      $container->get('jcc_elevated_roc.rule_list.service'),
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

    $build['roc_list'] = $this->jccElevatedRocRuleList->getList();

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
