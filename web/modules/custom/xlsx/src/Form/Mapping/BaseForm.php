<?php

namespace Drupal\xlsx\Form\Mapping;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\xlsx\Plugin\XlsxSourceManager;
use Drupal\xlsx\Plugin\XlsxCellManager;
use Drupal\xlsx\Plugin\XlsxExportManager;
use Drupal\xlsx\Plugin\XlsxDataManager;
use Drupal\xlsx\Plugin\XlsxRemoteManager;

/**
 * Abstract class for the XLSX mapping tool.
 *
 * @ingroup xlsx
 */
abstract class BaseForm extends FormBase {

  use DependencySerializationTrait;

  protected $store_variable_by_step = [
    'xlsx_new_mapping_form' => [
      'name', 'source', 'export_only', 'xlsx_entity',
    ],
    'xlsx_source_mapping_form' => [
      'source_uploaded', 'sheet_columns', 'csv', 'source_name', 'source_file_id',
    ],
    'xlsx_entity_mapping_form' => [
      'entity_mapping', 'curr_index',
    ],
    'xlsx_fields_mapping_form' => [
      'field_mapping',
    ],
    'xlsx_review_mapping_form' => [
      'export'
    ],
  ];

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * @var \Drupal\user\PrivateTempStore
   */
  protected $store;

  /**
   * @var \Drupal\xlsx\Plugin\XlsxSourceManager
   */
  protected $xlsxSourceManager;

  /**
   * @var \Drupal\xlsx\Plugin\XlsxCellManager
   */
  protected $xlsxCellManager;

  /**
   * @var \Drupal\xlsx\Plugin\XlsxExportManager
   */
  protected $xlsxExportManager;

  /**
   * @var \Drupal\xlsx\Plugin\XlsxDataManager
   */
  protected $xlsxDataManager;

  /**
   * @var \Drupal\xlsx\Plugin\XlsxRemoteManager
   */
  protected $xlsxRemoteManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a \Drupal\ps_plans\Form\MappingBaseForm.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\xlsx\XlsxSourceManager $xlsx_source
   * @param \Drupal\xlsx\XlsxCellManager $xlsx_cell
   * @param \Drupal\xlsx\XlsxExportManager $xlsx_export
   * @param \Drupal\xlsx\XlsxDataManager $xlsx_data
   * @param \Drupal\xlsx\XlsxRemoteManager $xlsx_remote
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, XlsxSourceManager $xlsx_source, XlsxCellManager $xlsx_cell, XlsxExportManager $xlsx_export, XlsxDataManager $xlsx_data, XlsxRemoteManager $xlsx_remote, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityFieldManager $entity_field_manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->store = $this->tempStoreFactory->get('xlsx_multistep_data');
    $this->xlsxSourceManager = $xlsx_source;
    $this->xlsxCellManager = $xlsx_cell;
    $this->xlsxExportManager = $xlsx_export;
    $this->xlsxDataManager = $xlsx_data;
    $this->xlsxRemoteManager = $xlsx_remote;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('plugin.manager.xlsx_source'),
      $container->get('plugin.manager.xlsx_cell'),
      $container->get('plugin.manager.xlsx_export'),
      $container->get('plugin.manager.xlsx_data'),
      $container->get('plugin.manager.xlsx_remote'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Mapping'),
      '#button_type' => 'primary',
      '#weight' => 10,
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' =>  Url::fromRoute('xlsx.mapping'),
      '#weight' => 10,
      '#attributes' => ['class' => 'button'],
    ];
    $form_state->disableCache();
    return $form;
  }

  /**
   * Save temporary data for each mapping step.
   */
  protected function saveData() {
    $mapping = [
      'source' => $this->store->get('source'),
      'source_name' => !empty($this->store->get('source_name')) ? $this->store->get('source_name') : NULL,
      'cron' => !empty($this->store->get('cron')) ? $this->store->get('cron') : NULL,
      'cron_frequency' => !empty($this->store->get('cron_frequency')) ? $this->store->get('cron_frequency') : NULL,
      'csv' => !empty($this->store->get('csv')) ? $this->store->get('csv') : NULL,
      'file_path' => !empty($this->store->get('file_path')) ? $this->store->get('file_path') : NULL,
      'export' => $this->store->get('export'),
      'export_only' => $this->store->get('export_only'),
      'entity_mapping' => $this->store->get('entity_mapping'),
      'field_mapping' => $this->store->get('field_mapping'),
      'sheet_columns' => $this->store->get('sheet_columns'),
    ];
    if ($source_file_id = $this->store->get('source_file_id')) {
      $mapping['source_file_id'] = $source_file_id;
    }
    $name = $this->store->get('name');
    if ($tmp_entity = $this->store->get('xlsx_entity')) {
      $xlsx = $tmp_entity;
    }
    else {
      $xlsx = $this->entityTypeManager->getStorage('xlsx')->create(['type' => 'xlsx']);
      $xlsx->set('id', preg_replace('@[^a-z0-9-]+@', '_', strtolower($name)));
    }
    $xlsx->set('label', $name);
    $xlsx->set('mapping', $mapping);
    if ($xlsx->save()) {
      if (isset($tmp_entity)) {
        $message = t('%name mapping successfully updated.', ['%name' => $name]);
      }
      else {
        $message = t('New mapping %name successfully created.', ['%name' => $name]);
      }
      $this->getLogger('xlsx')->notice($message);
      $this->messenger()->addStatus($message);
    }
    else {
      $message = t('Could not save %name mapping.', ['%name' => $name]);
      $this->getLogger('xlsx')->error($message);
      $this->messenger()->addWarning($message);
    }
    $this->deleteStore();
  }

  /**
   * Delete data from temporary storage.
   */
  protected function deleteStoreByFormIds($form_ids = []) {
    // @TODO: Make this configurable in the UI.
    if ($form_ids) {
      foreach ($form_ids as $form_id) {
        if (!empty($this->store_variable_by_step[$form_id])) {
          foreach ($this->store_variable_by_step[$form_id] as $key) {
            $this->store->delete($key);
          }
        }
      }
    }
  }

  /**
   * Delete data from temporary storage.
   */
  protected function deleteStore() {
    foreach ($this->store_variable_by_step as $form_id => $keys) {
      foreach ($keys as $key) {
        $this->store->delete($key);
      }
    }
  }

  /**
   * Add loaded mapping to temporary storage.
   */
  protected function populateTmp($xlsx) {
    // Lets make sure we cleanup all other tmp storage before loading edit form.
    $this->deleteStore();
    $this->store->set('name', $xlsx->label());
    foreach ($xlsx->getMapping() as $key => $map) {
      $this->store->set($key, $map);
    }
    $this->store->set('source_uploaded', TRUE);
    $this->store->set('xlsx_entity', $xlsx);
  }

  /**
   * Redirect to listing page.
   */
  protected function redirecToListing() {
    return new RedirectResponse(Url::fromRoute('xlsx.mapping')->toString());
  }

  /**
   * Convert plugin ID to URL parameter.
   */
  protected function pluginToUrlParam($plugin_id) {
    return str_replace('_', '-',  $plugin_id);
  }

  /**
   * Helper function to get a list of Drupal fields.
   */
  protected function getFields($fields) {
    $excluded_fields = [
      'uuid', 'type', 'id', 'nid', 'vid', 'revision_uid', 'revision_timestamp', 'revision_log', 'revision_default', 'revision_translation_affected',
      'default_langcode', 'revision_user', 'revision_created', 'revision_id'
    ];
    $result = [];
    foreach ($fields as $field_name => $info) {
      if (!in_array($field_name, $excluded_fields)) {
        $result[$field_name] = [
          'label' => $info->getLabel(),
          'type' => $info->getType(),
        ];
      }
    }
    return $result;
  }

  /**
   * Get XLSX columns.
   */
  protected function getXLSXColumns($sheet_name) {
    $sheet_columns = $this->store->get('sheet_columns');
    if (!empty($sheet_columns[$sheet_name])) {
      $sheet_columns[$sheet_name][] = '';
      return $sheet_columns[$sheet_name];
    }
  }

  /**
   * This function prepopulates mapping fields by comparing Drupal field name with XLSX column name.
   */
  protected function getXLSXSuggestedColumn($columns, $field_label) {
    foreach ($columns as $key => $val) {
      if (preg_match("/{$field_label}/i", $val)) {
        return $key;
      }
    }
  }

  /**
   * Get XLSX columns.
   */
  protected function getXLSXColumnsByIndex($index) {
    $sheet_columns = $this->store->get('sheet_columns');
    if (!empty($sheet_columns)) {
      $keys = array_keys($sheet_columns);
      return $sheet_columns[$keys[$index]];
    }
  }

  /**
   * Get entity types and bundles.
   */
  protected function getEntityBundles() {
    // Defaults to empty value.
    $content_entity_types = [];
    $entity_type_definations = $this->entityTypeManager->getDefinitions();
    foreach ($this->xlsxDataManager->getDefinitions() as $plugin) {
      $xlsx_data = $this->xlsxDataManager->createInstance($plugin['id']);
      foreach ($entity_type_definations as $definition) {
        if ($definition instanceof ContentEntityType) {
          if ($xlsx_data->getEntityType() == $definition->id() && \Drupal::moduleHandler()->moduleExists($xlsx_data->getModule())) {
            $bundles = $this->entityTypeBundleInfo->getBundleInfo($definition->id());
            foreach ($bundles as $bundle => $info) {
              $content_entity_types[$xlsx_data->getPluginId() . '::' . $definition->id() . '::' . $bundle] = $this->t('@plugin: @bundle (@entity)', [
                '@plugin' => $xlsx_data->getName(), '@bundle' => $info['label'], '@entity' => $definition->id()
              ]);
            }
          }
        }
      }
    }
    return $content_entity_types;
  }

  /**
   * Get XLSX cell plugins.
   */
  protected function getCellPlugins($field_type = NULL) {
    $plugins = [];
    $plugin = $this->xlsxCellManager->createInstance('as_is');
    $plugins['as_is'] = $plugin->getName();
    foreach ($this->xlsxCellManager->getDefinitions() as $id => $plugin) {
      $plugin = $this->xlsxCellManager->createInstance($plugin['id']);
      if (in_array($field_type, $plugin->getFieldTypes()) && !empty($field_type) && !empty($plugin->getFieldTypes())) {
        $plugins[$id] = $plugin->getName();
      }
    }
    return $plugins;
  }

  /**
   * Temporarily set worksheet mapping.
   */
  protected function setFieldMapping($worksheet_index, $map) {
    $mapping = $this->store->get('field_mapping', []);
    foreach ($map as $field_name => $info) {
      if (isset($info['column']) && strlen(trim($info['column'])) != 0) {
        $mapping[$worksheet_index][$field_name] = [
          'column' => $info['column'],
          'cell_plugin' => $info['cell_plugin'],
        ];
      }
    }
    $this->store->set('field_mapping', $mapping);
  }

  /**
   * Get temporary field mapping.
   */
  protected function getFieldMapping($worksheet_index) {
    $mapping = $this->store->get('field_mapping');
    if (!empty($mapping[$worksheet_index])) {
      return $mapping[$worksheet_index];
    }
  }

  /**
   * Load entity by entity type and mapping ID.
   */
  protected function loadEntitiesByMapping($mapping_id) {
    $result = \Drupal::entityQuery('xlsx_data')->condition('mapping_id', $mapping_id)->execute();
    if ($ids = array_values($result)) {
      return \Drupal::entityTypeManager()->getStorage('xlsx_data')->loadMultiple($ids);
    }
  }

}
