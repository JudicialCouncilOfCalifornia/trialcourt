<?php

namespace Drupal\jcc_elevated_embeds\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\AdminContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'JccTestEmbed' block.
 *
 * @Block(
 *  id = "jcc_test_embed",
 *  admin_label = @Translation("JCC Test Embed"),
 * )
 */
class JccTestEmbedBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The admin context.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AdminContext $admin_context) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->adminContext = $admin_context;
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

    $form['test'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test value'),
      '#default_value' => $this->configuration['test'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->configuration['test'] = $values['test'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    if ($this->adminContext->isAdminRoute()) {
      return $this->getAdminBuild();
    }

    $build['test'] = [
      '#markup' => $this->t('This is a test embed with test value: @value', ['@value' => $this->configuration['test'] ?? 'None']),
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
      '#markup' => $this->t('This is a test embed with test value: @value', ['@value' => $this->configuration['test'] ?? 'None']),
      '#suffix' => '</span></div></div>',
    ];

    return $build;
  }

}
