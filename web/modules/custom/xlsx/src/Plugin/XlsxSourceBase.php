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
use Drupal\file\Entity\File;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * XlsxSource plugin base class.
 *
 * @package xlsx
 */
class XlsxSourceBase extends PluginBase implements XlsxSourceInterface, ContainerFactoryPluginInterface {

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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileSystemInterface $file_system, PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileSystem = $file_system;
    $this->tempStoreFactory = $temp_store_factory;
    $this->store = $this->tempStoreFactory->get('xlsx_multistep_data');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('file_system'),
      $container->get('tempstore.private'),
      $container->get('entity_type.manager')
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
  public function isCron() {
    return !empty($this->pluginDefinition['cron']);
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
  public function sourceForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitSourceForm(array &$form, FormStateInterface $form_state) {
    
  }

  /**
   * {@inheritdoc}
   */
  public function importForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitImportForm(array &$form, FormStateInterface $form_state, $xlsx = NULL) {
    
  }

  /**
   * {@inheritdoc}
   */
  public function getWorksheets() {
    $worksheets = [];
    $sheet_columns = $this->store->get('sheet_columns');
    foreach ($sheet_columns as $worksheet => $info) {
      $worksheets[] = $worksheet;
    }
    return $worksheets;
  }

  /**
   * Process uploaded XLSX file to pull columns.
   */
  protected function getColumnsFromFile($file_id, $file_type) {
    $file = File::load($file_id);

    $reader = IOFactory::createReader($file_type);
    if ($file_type == 'Csv') {
      $params = $this->store->get('csv');
      $reader->setDelimiter($params['delimiter']);
      $reader->setEnclosure($params['enclosure']);
      $reader->setEscapeCharacter($params['escape']);
      $reader->setInputEncoding($params['encoding']);
    }
    $spreadsheet = $reader->load($this->fileSystem->realpath($file->getFileUri()));
    
    $sheet_columns = [];
    $loadedSheetNames = $spreadsheet->getSheetNames();
    foreach ($loadedSheetNames as $sheet_name) {
      $spreadsheet->setActiveSheetIndexByName($sheet_name);
      $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
      if (!empty($sheetData[1])) {
        foreach ($sheetData[1] as $key => $value) {
          if (!empty($value)) {
            $sheet_columns[$sheet_name][$key] = $value;
          }
        }
      }
    }
    $this->store->set('source_name', $file->getFilename());
    return $sheet_columns;
  }

  /**
   * Process XLSX file from absolute path to pull columns.
   */
  protected function getColumnsFromFilePath($filepath, $file_type) {
    if (filter_var($filepath, FILTER_VALIDATE_URL)) {
      $filepath = system_retrieve_file($filepath);
    }
    $reader = IOFactory::createReader($file_type);
    if ($file_type == 'Csv') {
      $params = $this->store->get('csv');
      $reader->setDelimiter($params['delimiter']);
      $reader->setEnclosure($params['enclosure']);
      $reader->setEscapeCharacter($params['escape']);
      $reader->setInputEncoding($params['encoding']);
    }
    $spreadsheet = $reader->load($this->fileSystem->realpath($filepath));
    
    $sheet_columns = [];
    $loadedSheetNames = $spreadsheet->getSheetNames();
    foreach ($loadedSheetNames as $sheet_name) {
      $spreadsheet->setActiveSheetIndexByName($sheet_name);
      $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
      if (!empty($sheetData[1])) {
        foreach ($sheetData[1] as $key => $value) {
          if (!empty($value)) {
            $sheet_columns[$sheet_name][$key] = $value;
          }
        }
      }
    }
    $this->store->set('source_name', basename($filepath));
    return $sheet_columns;
  }

  /**
   * Build XLSX data import structure.
   */
  public function buildBatchItems($xlsx_entity, $file_id, $file_type) {
    $mapping = $xlsx_entity->getMapping();
    $file = File::load($file_id);
    $reader = IOFactory::createReader($file_type);
    if ($file_type == 'Csv') {
      $params = $mapping['csv'];
      $reader->setDelimiter($params['delimiter']);
      $reader->setEnclosure($params['enclosure']);
      $reader->setEscapeCharacter($params['escape']);
      $reader->setInputEncoding($params['encoding']);
    }
    $spreadsheet = $reader->load($this->fileSystem->realpath($file->getFileUri()));
    $loadedSheetNames = $spreadsheet->getSheetNames();
    $items = [];
    $index = 0;
    foreach ($loadedSheetNames as $sheet_name) {
      foreach ($mapping['entity_mapping'] as $i => $info) {
        if ($info['worksheet']['index'] == $index && $info['worksheet']['name'] == $sheet_name) {
          $cleanSheetData = $this->cleanupSheetData($spreadsheet, $sheet_name);
          if (!empty($cleanSheetData)) {
            foreach ($cleanSheetData as $data) {
              $items[] = ['\Drupal\xlsx\XlsxBatchOps::import', [$xlsx_entity, $info['entity'], $index, $sheet_name, $data]];
            }
          }
        }
      }
      $index++;
    }
    return $items;
  }

  /**
   * Build XLSX data import structure (for a file path).
   */
  public function buildBatchItemsFilePath($xlsx_entity, $filepath, $file_type) {
    if (filter_var($filepath, FILTER_VALIDATE_URL)) {
      $filepath = system_retrieve_file($filepath);
    }
    $mapping = $xlsx_entity->getMapping();
    $reader = IOFactory::createReader($file_type);
    if ($file_type == 'Csv') {
      $params = $mapping['csv'];
      $reader->setDelimiter($params['delimiter']);
      $reader->setEnclosure($params['enclosure']);
      $reader->setEscapeCharacter($params['escape']);
      $reader->setInputEncoding($params['encoding']);
    }
    $spreadsheet = $reader->load($this->fileSystem->realpath($filepath));
    $loadedSheetNames = $spreadsheet->getSheetNames();
    $items = [];
    $index = 0;
    foreach ($loadedSheetNames as $sheet_name) {
      foreach ($mapping['entity_mapping'] as $i => $info) {
        if ($info['worksheet']['index'] == $index && $info['worksheet']['name'] == $sheet_name) {
          $cleanSheetData = $this->cleanupSheetData($spreadsheet, $sheet_name);
          if (!empty($cleanSheetData)) {
            foreach ($cleanSheetData as $data) {
              $items[] = ['\Drupal\xlsx\XlsxBatchOps::import', [$xlsx_entity, $info['entity'], $index, $sheet_name, $data]];
            }
          }
        }
      }
      $index++;
    }
    return $items;
  }

  /**
   * Simplify data structure pulled from a spreadsheet.
   */
  protected function cleanupSheetData($spreadsheet, $sheet_name) {
    $spreadsheet->setActiveSheetIndexByName($sheet_name);
    $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
    if (!empty($sheetData)) {
      $cleanSheetData = [];
      $rows = 0;
      foreach ($sheetData as $key => $value) {
        if (!empty($value) && $rows != 0) {
          $cleanSheetData[][$key] = $value;
        }
        $rows++;
      }
    }
    return $cleanSheetData;
  }

}
