<?php

namespace Drupal\xlsx\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\xlsx\Plugin\XlsxSourceManager;
use Drupal\xlsx\Plugin\XlsxExportManager;
use Drupal\xlsx\Plugin\XlsxDataManager;
use Drupal\xlsx\Plugin\XlsxRemoteManager;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Document\Security;
use Drupal\xlsx\Spreadsheets;
use Drupal\webform\Entity\WebformSubmission;

/**
 * XlsxExport plugin base class.
 *
 * @package xlsx
 */
class XlsxExportBase extends PluginBase implements XlsxExportInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * @var \Drupal\user\PrivateTempStore
   */
  protected $store;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileSystemInterface $file_system, PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, XlsxSourceManager $xlsx_source, XlsxCellManager $xlsx_cell, XlsxExportManager $xlsx_export, XlsxDataManager $xlsx_data, XlsxRemoteManager $xlsx_remote) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileSystem = $file_system;
    $this->tempStoreFactory = $temp_store_factory;
    $this->store = $this->tempStoreFactory->get('xlsx_multistep_data');
    $this->entityTypeManager = $entity_type_manager;
    $this->xlsxSourceManager = $xlsx_source;
    $this->xlsxCellManager = $xlsx_cell;
    $this->xlsxExportManager = $xlsx_export;
    $this->xlsxDataManager = $xlsx_data;
    $this->xlsxRemoteManager = $xlsx_remote;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('file_system'),
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.xlsx_source'),
      $container->get('plugin.manager.xlsx_cell'),
      $container->get('plugin.manager.xlsx_export'),
      $container->get('plugin.manager.xlsx_data'),
      $container->get('plugin.manager.xlsx_remote')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceTypes() {
    return $this->pluginDefinition['source_types'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDataType() {
    return $this->pluginDefinition['data_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function classExists() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function exportForm(array $form, FormStateInterface $form_state, $xlsx = NULL) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitExportForm(array &$form, FormStateInterface $form_state) {
    
  }

  /**
   * Run export.
   */
  protected function runExport($xlsx, $export_plugin_id, $remote_plugin_id, $params = []) {
    $mapping = $xlsx->getMapping();
    $entity_mapping = $mapping['entity_mapping'];
    $field_mapping = $mapping['field_mapping'];

    $modified_by = '';
    if ($current_user = \Drupal::currentUser()->getAccount()) {
      $modified_by = $current_user->getAccountName() . ' (' . $current_user->getEmail() . ')';
    }

    $obj = new Spreadsheet();
    $obj->getProperties()->setCreator('Spreadsheets Drupal 8 module')
        ->setLastModifiedBy($modified_by)
        ->setTitle($xlsx->label())
        ->setSubject('Exported Data');

    $password = '';
    if (!empty($params['password_protected']) && !empty($params['password'])) {
      $password = $params['password'];
    }

    $spreadsheet = Spreadsheets::newSpreadsheet($obj);

    try {
      $worksheet_index = 0;
      foreach ($mapping['sheet_columns'] as $sheet_name => $columns) {
        $headers = array_values($columns);
        $spreadsheet->setSheet(($worksheet_index === 0 ? $worksheet_index : NULL), $sheet_name);
        $spreadsheet->addRow($headers);
        if (!empty($entity_mapping[$worksheet_index])) {
          $entity = $entity_mapping[$worksheet_index]['entity'];
          $rows = $this->getExportData($xlsx, $field_mapping[$worksheet_index], $columns, $entity['xlsx_data_plugin'], $entity['type'], $entity['bundle']);
          if (!empty($rows)) {
            foreach ($rows as $row) {
              $spreadsheet->addRow($row);
            }
          }
        }
        $worksheet_index++;
      }
    }
    catch (\Exception $e) {
      print_r($e->getMessage());exit;
    }
    $xlsx->setLastExport();
    $xlsx_export = $this->xlsxExportManager->createInstance($export_plugin_id);
    $xlsx_remote = $this->xlsxRemoteManager->createInstance($remote_plugin_id);
    Spreadsheets::process($xlsx, $xlsx_export, $xlsx_remote, $password);
  }

  /**
   * Get exportable data.
   */
  protected function getExportData($xlsx, $field_mapping, $columns, $xlsx_data_plugin, $entity_type, $entity_bundle) {
    $rows = [];
    $xlsx_data = $this->xlsxDataManager->createInstance($xlsx_data_plugin);
    if ($entity_type == 'webform_submission') {
      $query = \Drupal::entityQuery('webform_submission')
        ->condition('webform_id', $entity_bundle)
        ->accessCheck(FALSE);
      $submissions = $query->execute();
      foreach ($submissions as $id) {
        $submission = WebformSubmission::load($id);
        $submission_data = $submission->getData();
        foreach ($columns as $key => $field) {
          if ($mapping = $this->getFieldMapping($key, $field_mapping)) {
            $xlsx_cell = !empty($mapping['cell_plugin']) ? $mapping['cell_plugin'] : 'as_is';
            if ($plugin = $this->xlsxCellManager->createInstance($xlsx_cell)) {
              $rows[$id][] = !empty($submission_data[$mapping['field']])
                ? $plugin->export($submission, $mapping['field'], $submission_data[$mapping['field']]) : '';
            }
            else {
              $rows[$id][] = !empty($submission_data[$mapping['field']])
                ? $submission_data[$mapping['field']] : '';
            }
          }
          else {
            $rows[$id][] = '';
          }
        }
      }
    }
    else {
      $entities = $xlsx_data->getEntities($xlsx, $entity_type, $entity_bundle);
      foreach ($entities as $entity) {
        foreach ($columns as $key => $field) {
          if ($mapping = $this->getFieldMapping($key, $field_mapping)) {
            $xlsx_cell = !empty($mapping['cell_plugin']) ? $mapping['cell_plugin'] : 'as_is';
            if ($plugin = $this->xlsxCellManager->createInstance($xlsx_cell)) {
              $rows[$entity->id()][] = $entity->hasField($mapping['field'])
                ? $plugin->export($entity, $mapping['field'], $entity->get($mapping['field'])->getValue()) : '';
            }
            else {
              $rows[$entity->id()][] = $entity->hasField($mapping['field'])
                ? $entity->get($mapping['field'])->value : '';
            }
          }
          else {
            $rows[$entity->id()][] = '';
          }
        }
      }
    }
    return $rows;
  }

  /**
   * Get worksheet mapping by index.
   */
  protected function getWorksheetIndexKey($entity_mapping, $worksheet_index) {
    foreach ($entity_mapping as $index => $info) {
      if ($info['worksheet']['index'] == $worksheet_index) {
        return $index;
      }
    }
  }

  /**
   * This function helps to build XLSX columns in its original order.
   */
  protected function getFieldMapping($key, $field_mapping) {
    foreach ($field_mapping as $field => $info) {
      if ($key == $info['column']) {
        return [
          'field' => $field,
          'cell_plugin' => $info['cell_plugin'],
        ];
      }
    }
  }

}
