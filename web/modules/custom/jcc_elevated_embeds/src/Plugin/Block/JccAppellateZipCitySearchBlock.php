<?php

namespace Drupal\jcc_elevated_embeds\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Url;
use Drupal\jcc_elevated_sections\JccSectionServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'JccAppellateZipCitySearch' block.
 *
 * @Block(
 *  id = "jcc_appellate_zcs",
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
   * The jcc section service.
   *
   * @var \Drupal\jcc_elevated_sections\JccSectionService
   */
  protected $jccSectionService;

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
   * @param \Drupal\jcc_elevated_sections\JccSectionServiceInterface $jcc_section_service
   *   JCC Section service.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager,
                              AdminContext $admin_context,
                              CacheBackendInterface $cache_backend,
                              ModuleHandlerInterface $module_handler,
                              JccSectionServiceInterface $jcc_section_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->adminContext = $admin_context;
    $this->cache = $cache_backend;
    $this->moduleHandler = $module_handler;
    $this->jccSectionService = $jcc_section_service;
    $this->nodeStorage = $entity_type_manager->getStorage('node');
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
      $container->get('jcc_elevated_sections.service'),
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
    $config = $this->getConfiguration();

    $form['#tree'] = TRUE;

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#rows' => 3,
      '#cols' => 60,
      '#type' => 'textarea',
      '#default_value' => $config['description'] ?? '',
    ];

    $form['view_all'] = [
      '#type' => 'details',
      '#title' => $this->t('Link one'),
    ];

    $form['view_all']['view_all_title'] = [
      '#title' => $this->t('Title'),
      '#type' => 'textfield',
      '#default_value' => $config['view_all_title'] ?? $this->t('See all courts'),
    ];

    $form['view_all']['view_all_url'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Url'),
      '#description' => $this->t('Internal or external urls are allowed.'),
      '#required' => FALSE,
      '#process_default_value' => FALSE,
      '#default_value' => $config['view_all_url'] ?? '',
      '#attributes' => [
        'data-autocomplete-first-character-blacklist' => '/#?',
      ],
      '#element_validate' => [
        [
          'Drupal\link\Plugin\Field\FieldWidget\LinkWidget',
          'validateUriElement',
        ],
      ],
    ];

    // Process the url for an entity or an external URL.
    if (strpos($form['view_all']['view_all_url']['#default_value'], 'entity:') === 0) {
      $value = explode('/', $form['view_all']['view_all_url']['#default_value']);
      $entity_id = end($value);
      $entity = \Drupal::entityTypeManager()->getStorage($form['view_all']['view_all_url']['#target_type'])->load($entity_id);
      $form['view_all']['view_all_url']['#default_value'] = $entity_id ? $entity : '';
      $form['view_all']['view_all_url']['#process_default_value'] = TRUE;
    }

    $form['fmc'] = [
      '#type' => 'details',
      '#title' => $this->t('Link two'),
    ];

    $form['fmc']['fmc_title'] = [
      '#title' => $this->t('Title'),
      '#type' => 'textfield',
      '#default_value' => $config['fmc_title'] ?? $this->t('Find'),
    ];

    $form['fmc']['fmc_url'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Url'),
      '#description' => $this->t('Internal or external urls are allowed.'),
      '#required' => FALSE,
      '#process_default_value' => FALSE,
      '#default_value' => $config['fmc_url'] ?? '',
      '#attributes' => [
        'data-autocomplete-first-character-blacklist' => '/#?',
      ],
      '#element_validate' => [
        [
          'Drupal\link\Plugin\Field\FieldWidget\LinkWidget',
          'validateUriElement',
        ],
      ],
    ];

    // Process the url for an entity or an external URL.
    if (strpos($form['fmc']['fmc_url']['#default_value'], 'entity:') === 0) {
      $value = explode('/', $form['fmc']['fmc_url']['#default_value']);
      $entity_id = end($value);
      $entity = \Drupal::entityTypeManager()->getStorage($form['fmc']['fmc_url']['#target_type'])->load($entity_id);
      $form['fmc']['fmc_url']['#default_value'] = $entity_id ? $entity : '';
      $form['fmc']['fmc_url']['#process_default_value'] = TRUE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['description'] = $values['description'];
    $this->configuration['view_all_title'] = $values['view_all']['view_all_title'];
    $this->configuration['view_all_url'] = $values['view_all']['view_all_url'];
    $this->configuration['fmc_title'] = $values['fmc']['fmc_title'];
    $this->configuration['fmc_url'] = $values['fmc']['fmc_url'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    $build = [];

    // This outputs on the admin side a simple summary of the embedded block.
    // Without this, it would try to render the whole block on the node admin.
    if ($this->adminContext->isAdminRoute()) {
      return $this->getAdminBuild();
    }

    $build['library'] = [
      '#markup' => FALSE,
      '#attached' => [
        'library' => ['jcc_elevated_embeds/jcc_appellate_zcs'],
      ],
    ];

    if ($config['description']) {
      $build['description'] = [
        '#prefix' => '<p>',
        '#markup' => $config['description'],
        '#suffix' => '</p>',
      ];
    }

    $build['form']['input'] = [
      '#type' => 'textfield',
      '#title' => 'Enter Zip Code, City or County',
      '#autocomplete_path' => '/admin/config/jcc-elevated/embeds/autocomplete/zip_city_county_info',
      '#attributes' => [
        'id' => 'zcs-input',
        'aria-labelledby' => 'jcc-zcs-label',
      ],
      '#suffix' => '<div class="block__jcc-appellate-zcs__form__matches" id="zcs-matches"></div>',
    ];

    $build['form']['actions'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['block__jcc-appellate-zcs__form__actions'],
      ],
    ];

    if ($config['view_all_url']) {
      $build['form']['actions']['view_all_link'] = [
        '#type' => 'link',
        '#title' => $config['view_all_title'] ?? $this->t('See all Courts'),
        '#url' => Url::fromUri($config['view_all_url']),
        '#attributes' => [
          'class' => [
            'button button--primary button--normal',
            'block__jcc-appellate-zcs__form__view-all',
          ],
        ],
      ];
    }

    if ($config['fmc_url']) {
      $build['form']['actions']['find_my_court_link'] = [
        '#type' => 'link',
        '#title' => $config['fmc_title'] ?? $this->t('Find'),
        '#url' => Url::fromUri($config['fmc_url']),
        '#attributes' => [
          'class' => [
            'button button--secondary button--normal',
            'block__jcc-appellate-zcs__form__submit',
          ],
        ],
      ];
    }

    $build['map'] = [
      '#type' => 'inline_template',
      '#template' => '<?xml-stylesheet type="text/css" href="jcc-appellate-zcs.css" ?>' . $this->getMap(),
    ];

    $build['map_links'] = $this->getMapLinks();

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

  /**
   * {@inheritdoc}
   */
  protected function getMap() {
    $path = $this->moduleHandler->getModule('jcc_elevated_embeds')->getPath();
    return file_get_contents($path . '/assets/appellate_map/svgs/JCC_California_Districts.svg');
  }

  /**
   * {@inheritdoc}
   */
  protected function getMapLinks() {
    $sections = $this->jccSectionService->getSections();

    $links = [];
    foreach ($sections as $section) {
      $section_homepage_nid = $section->jcc_section_homepage->target_id ?? FALSE;
      $section_machine_name = $section->jcc_section_machine_name->value ?? '';

      // Adding this string check just in case another section is added
      // that is not part of the District list (like a Division or Subgroup).
      if ($section_homepage_nid && str_contains($section_machine_name, 'district_')) {
        $options = [
          'attributes' => [
            'class' => ['zcs-map-link__item', $section_machine_name],
            'name' => ucfirst($section_machine_name),
          ],
        ];
        $url = Url::fromRoute('entity.node.canonical', ['node' => $section_homepage_nid], $options);
        $links[] = [
          '#markup' => Link::fromTextAndUrl($section->label(), $url)->toString(),
        ];
      }
    }

    return $links;
  }

}
