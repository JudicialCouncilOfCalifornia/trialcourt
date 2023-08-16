<?php

namespace Drupal\jcc_elevated_embeds\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\AdminContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'JccAppellateZipCitySearch' block.
 *
 * @Block(
 *  id = "jcc_appellate_zip_city_search",
 *  admin_label = @Translation("JCC Appellate Zip/City Search Block"),
 * )
 */
class JccAppellateZipCitySearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * {@inheritdoc}
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // This outputs on the admin side a simple summary of the embedded block.
    // Without this, it would try to render the whole block on the node admin.
    if ($this->adminContext->isAdminRoute()) {
      return $this->getAdminBuild();
    }

    $build['form'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search by Zip or City name'),
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
      '#markup' => $this->label() ?? $this->t('JCC Appellate Zip/City Search'),
      '#suffix' => '</span></div></div>',
    ];

    return $build;
  }

}
