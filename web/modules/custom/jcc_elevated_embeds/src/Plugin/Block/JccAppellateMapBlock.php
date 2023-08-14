<?php

namespace Drupal\jcc_elevated_embeds\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\AdminContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'JccAppellateMap' block.
 *
 * @Block(
 *  id = "jcc_appellate_map",
 *  admin_label = @Translation("JCC Appellate Map Block"),
 * )
 */
class JccAppellateMapBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $node_manager = $this->entityTypeManager->getStorage('node');
    $config = $this->getConfiguration();

    $district_info = $this->getDistricts();

    $form['#tree'] = TRUE;

    $form['districts'] = [
      '#type' => 'details',
      '#title' => $this->t('District urls/pages'),
      '#description' => $this->t('Assign a page to each District. This will set the links for the map and legend.'),
      '#open' => TRUE,
    ];

    foreach ($district_info as $district) {

      $id = $district['district_id'];
      $entity = is_numeric($config[$id . '_page']) ? $node_manager->load($config[$id . '_page']) : NULL;

      $form['districts'][$id . '_page'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'node',
        '#selection_settings' => [
          'target_bundles' => ['landing_page', 'subpage'],
        ],
        '#title' => $this->t('@district_name Page', ['@district_name' => $district['district_name']]),
        '#description' => $this->t('Assign a page or url to this District. This will set the links for the map and legend.'),
        '#default_value' => $entity,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $district_info = $this->getDistricts();

    foreach ($district_info as $district) {
      $id = $district['district_id'];
      $this->configuration[$id . '_page'] = $values['districts'][$id . '_page'];
    }
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

    $district_info = $this->getCountiesByDistrict();

    $legend = [
      '#theme' => 'jcc_appellate_district_legend',
      '#district_info' => $district_info,
    ];

    $map_viewport = [
      '#theme' => 'jcc_appellate_viewport',
      '#district_info' => $district_info,
      '#map_src' => [
        '#type' => 'inline_template',
        '#template' => '<?xml-stylesheet type="text/css" href="jcc-appellate-map.css" ?>' . $this->getMap(),
      ],
    ];

    $build['output'] = [
      '#theme' => 'jcc_appellate_map',
      '#legend' => $legend,
      '#map_viewport' => $map_viewport,
    ];

    $build['#attached']['library'][] = 'jcc_elevated_embeds/jcc_appellate_map';

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
      '#markup' => $this->label() ?? $this->t('JCC Appellate Map'),
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

  /**
   * {@inheritdoc}
   */
  protected function getCountiesByDistrict(): array {
    $node_manager = $this->entityTypeManager->getStorage('node');
    $config = $this->getConfiguration();

    $info = [];
    foreach ($this->getFullDistrictInformation() as $row) {
      $num = $row['district'];
      $id = $row['district_id'];
      $county = $row['county'];
      $county_id = $row['county_id'];

      $entity = is_numeric($config[$id . '_page']) ? $node_manager->load($config[$id . '_page']) : NULL;

      $info[$id]['district_number'] = $num;
      $info[$id]['district_id'] = $id;
      $info[$id]['district_name'] = $this->t('@num District', ['@num' => $this->getNumber($num)])->render();
      $info[$id]['district_link_title'] = $this->t('View @num District website', ['@num' => $this->getNumber($num)])->render();
      $info[$id]['district_link_url'] = $entity ? $entity->toUrl()->toString() : '';
      $info[$id]['counties'][$county_id] = $county;
    }

    ksort($info);

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDistricts(): array {
    $cacheId = 'jcc_elevated_embeds:appellate_map_district_info';

    if ($cache = $this->cache->get($cacheId)) {
      $info = $cache->data;
    }
    else {
      $config = $this->getConfiguration();

      $info = [];
      foreach ($this->getFullDistrictInformation() as $row) {
        $num = $row['district'];
        $id = $row['district_id'];
        $info[$id]['district_number'] = $num;
        $info[$id]['district_id'] = $id;
        $info[$id]['district_name'] = $this->t('@num District', ['@num' => $this->getNumber($num)])->render();
        $info[$id]['district_link_title'] = $this->t('View @num District website', ['@num' => $this->getNumber($num)])->render();
      }

      ksort($info);

      $this->cache->set($cacheId, $info, CacheBackendInterface::CACHE_PERMANENT);
    }

    $config = $this->getConfiguration();
    $node_manager = $this->entityTypeManager->getStorage('node');

    // Add our entity links.
    foreach ($info as $id => $district) {
      $entity = is_numeric($config[$id . '_page']) ? $node_manager->load($config[$id . '_page']) : NULL;
      $info[$id]['district_link_url'] = $entity ? $entity->toUrl()->toString() : '';
    }

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  protected function getNumber($number) {
    if (empty($number) || !is_numeric($number)) {
      return '';
    }

    switch ($number) {
      case 1:
        $suffix = 'st';
        break;

      case 2:
        $suffix = 'nd';
        break;

      case 3:
        $suffix = 'rd';
        break;

      default:
        $suffix = 'th';
        break;
    }

    return $number . $suffix;
  }

  /**
   * {@inheritdoc}
   */
  protected function getMap() {
    $path = $this->moduleHandler->getModule('jcc_elevated_embeds')->getPath();
    return file_get_contents($path . '/assets/appellate_map/svgs/JCC_California_Districts.svg');
  }

}
