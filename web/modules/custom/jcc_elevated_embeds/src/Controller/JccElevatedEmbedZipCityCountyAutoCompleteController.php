<?php

namespace Drupal\jcc_elevated_embeds\Controller;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\jcc_elevated_sections\JccSectionServiceInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;

/**
 * Defines a route controller for watches autocomplete form elements.
 */
class JccElevatedEmbedZipCityCountyAutoCompleteController extends ControllerBase {

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $nodeStorage;

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
   * The path alias handler service.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The jcc section service.
   *
   * @var \Drupal\jcc_elevated_sections\JccSectionService
   */
  protected $jccSectionService;

  /**
   * Constructs media integration plugin.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   Admin route context service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   Path alias manager handler.
   * @param \Drupal\jcc_elevated_sections\JccSectionServiceInterface $jcc_section_service
   *   JCC Section service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              AdminContext $admin_context,
                              CacheBackendInterface $cache_backend,
                              ModuleHandlerInterface $module_handler,
                              AliasManagerInterface $alias_manager,
                              JccSectionServiceInterface $jcc_section_service) {
    $this->entityTypeManager = $entity_type_manager;
    $this->adminContext = $admin_context;
    $this->cache = $cache_backend;
    $this->moduleHandler = $module_handler;
    $this->aliasManager = $alias_manager;
    $this->jccSectionService = $jcc_section_service;
    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('router.admin_context'),
      $container->get('cache.default'),
      $container->get('module_handler'),
      $container->get('path_alias.manager'),
      $container->get('jcc_elevated_sections.service'),
    );
  }

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {

    $results = [];
    $input = $request->query->get('q');

    // Get the typed string from the URL, if it exists.
    if (!$input || strlen($input) <= 2) {
      return new JsonResponse([$this->t('Please enter more characters for your search.')]);
    }

    // Grab our input, and break it up if a multiple terms with the full search
    // as the first searched item. So "Valley springs" would be searched as
    // "Valley springs", "Valley", and "springs".
    $input = trim(strtolower(Xss::filter($input)));
    $input_terms = explode(' ', $input);
    if (count($input_terms) > 1) {
      array_unshift($input_terms, $input);
    }

    // Remove CA and County from searching terms (they are on every item).
    $cleaned_input_terms = array_filter($input_terms, function ($term) {
      // We don't use ca, county, or san as extra search terms because they will
      // return too much data.
      return ($term !== 'ca') && ($term !== 'county') && ($term !== 'san') ? $term : FALSE;
    });

    $items = $this->getData();

    // Go through each individual term and collect results.
    foreach ($cleaned_input_terms as $input_term) {
      $results += array_filter($items, function ($location_data) use ($input_term) {
        $input_term = preg_replace('/[\W]/', '', $input_term);
        return strpos(strtolower($location_data), $input_term) !== FALSE;
      }, ARRAY_FILTER_USE_KEY);
    }

    if (empty($results)) {
      return new JsonResponse([$this->t('No results found. Please try another search.')]);
    }

    // Return only the first 10 items.
    $results = array_slice($results, 0, 40);

    return new JsonResponse($results);
  }

  /**
   * Handler for autocomplete request.
   */
  protected function getCsvData() {
    $data = [];

    $modulePath = $this->moduleHandler->getModule('jcc_elevated_embeds')->getPath();
    $csv = $modulePath . "/assets/appellate_zip_city_county_search/JccDistrictsByZipCityCounty__ProcessedInformation.csv";

    $file = fopen($csv, 'r');
    while (!feof($file)) {
      $csv_file = fgetcsv($file, NULL, ",");
      $data[$csv_file[0]] = $csv_file[1];
    }
    fclose($file);

    unset($data['Location_data']);

    return $data;
  }

  /**
   * Handler for autocomplete request.
   */
  protected function getData() {
    $cacheId = 'jcc_elevated_embeds:appellate_zcs_processed_source_data';

    if ($cache = $this->cache->get($cacheId)) {
      return $cache->data;
    }

    $data = $this->getCsvData();

    $section_service = $this->jccSectionService;
    $sections = $section_service->getSections();

    $urls = [];
    foreach ($sections as $section) {
      $machine_name = $section->get('jcc_section_machine_name')->value;
      $sh_nid = $section->get('jcc_section_homepage')->target_id;
      $alias = $this->aliasManager->getAliasByPath('/node/' . $sh_nid);
      $urls[$machine_name] = [
        'machine_name' => $machine_name,
        'name' => $section->label(),
        'sid' => $section->id(),
        'url' => $alias,
      ];
    }

    // District ID from data should match machine name from above.
    $revised_data = [];
    foreach ($data as $zip_city_info => $district_id) {
      $info = explode(' | ', $zip_city_info);
      $zip = $info[0];
      $city = str_replace(", CA", "", $info[1]);
      $county = str_replace(" County", "", $info[2]);
      $revised_data[$zip . " | " . $county . " | " . $city] = [
        'district' => $urls[$district_id]['name'],
        'machine_name' => $urls[$district_id]['machine_name'],
        'url' => $urls[$district_id]['url'],
        'search' => $zip . " | " . $city . " | " . $county,
        'zip' => $info[0],
        'city' => $city,
        'county' => $county,
      ];
    }

    $this->cache->set($cacheId, $revised_data, CacheBackendInterface::CACHE_PERMANENT);

    return $revised_data;
  }

}
