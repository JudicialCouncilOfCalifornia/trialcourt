<?php

namespace Drupal\jcc_elevated_embeds\Plugin\Block;

use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The admin context.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs media integration plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   Admin route context service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AdminContext $admin_context, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->adminContext = $admin_context;
    $this->cache = $cache_backend;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('router.admin_context'),
      $container->get('cache.default'),
      $container->get('module_handler'),
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
    $form = [];

    // This outputs on the admin side a simple summary of the embedded block.
    // Without this, it would try to render the whole block on the node admin.
    if ($this->adminContext->isAdminRoute()) {
      return $this->getAdminBuild();
    }

    //$form = \Drupal::formBuilder()->getForm('Drupal\jcc_elevated_embeds\Form\JccElevatedEmbedsZipCityCountySearchForm');

    return $form;
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

  /**
   * {@inheritdoc}
   */
  protected function getFullDistrictInformation(): array {
    $cacheId = 'jcc_elevated_embeds:appellate_map_full_district_info';

    if ($cache = $this->cache->get($cacheId)) {
      return $cache->data;
    }

    $url = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vSyg7Rvs4sYh7zEDx0jv0HH9aUTw2aLarcULPeI2UYrxsZALv45MBYb5rvQ_h3s8fxXp43uiGpOhfcG/pub?gid=0&single=true&output=csv';
    $data = array_map('str_getcsv', file($url));
    // Remove first line, which is just header info.
    $headers = array_shift($data);
    $info = [];
    foreach ($data as $id => $row) {
      $info[$id] = array_combine($headers, $row);
    }

    $this->cache->set($cacheId, $info, CacheBackendInterface::CACHE_PERMANENT);

    return $info;
  }
}
