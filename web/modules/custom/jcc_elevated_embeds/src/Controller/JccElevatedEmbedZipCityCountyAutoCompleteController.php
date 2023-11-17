<?php

namespace Drupal\jcc_elevated_embeds\Controller;

use ArrayQuery\QueryBuilder;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\AdminContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\Element\EntityAutocomplete;

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
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AdminContext $admin_context, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->adminContext = $admin_context;
    $this->cache = $cache_backend;
    $this->moduleHandler = $module_handler;
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
    );
  }

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {

    $results = [];
    $input = $request->query->get('q');

    // Get the typed string from the URL, if it exists.
    if (!$input || strlen($input) <= 2 ) {
      return new JsonResponse([t('Please enter more characters for your search.')]);
    }

    $input = Xss::filter($input);
    $items = $this->getCsvData();

    $results = array_filter($items, function($location_data) use ($input){
      return strpos($location_data, $input) !== FALSE;
    }, ARRAY_FILTER_USE_KEY);

    if (empty($results)) {
      return new JsonResponse([t('No results found. Please try another search.')]);
    }

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
      $csv_file = fgetcsv($file, '', ",");
      $data[$csv_file[0]] = $csv_file[1];
    }
    fclose($file);
    unset($data['Location_data']);
    return $data;
  }





//    $input = Xss::filter($input);
//    $items = $this->getCsvData();
//
//    $qb = QueryBuilder::create($items);
//    $qb->sortedBy('city', 'DESC')
//      ->limit(0, 10);
//
//    $qb->addCriterion(0, $input, 'CONTAINS');
//    if (is_numeric($input)) {
//      $qb->addCriterion('zip', $input, 'CONTAINS');
//    }
//    else {
//      $qb->addCriterion('city', $input, 'CONTAINS');
//    }
//
//    $qresults = $qb->getResults();
//
//    if (empty($qresults)) {
//      return new JsonResponse([t('No results found. Please try another search.')]);
//    }
//    foreach ($qresults as $item) {
//      $results[] = $item['full'];
//    }
}
