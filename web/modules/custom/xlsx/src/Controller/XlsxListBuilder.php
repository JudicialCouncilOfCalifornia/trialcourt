<?php

namespace Drupal\xlsx\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\xlsx\Plugin\XlsxSourceManager;
use Drupal\xlsx\Plugin\XlsxExportManager;
use Drupal\xlsx\Form\LicenseForm;


/**
 * Provides a listing of XLSX.
 */
class XlsxListBuilder extends ConfigEntityListBuilder {

  /**
   * @var \Drupal\xlsx\Plugin\XlsxSourceManager
   */
  protected $xlsxSourceManager;

  /**
   * @var \Drupal\xlsx\Plugin\XlsxExportManager
   */
  protected $xlsxExportManager;

  /**
   * Constructs a new XlsxListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\xlsx\XlsxSourceManager $xlsx_export
   *   The XLSX source plugin manager.
   * @param \Drupal\xlsx\XlsxExportManager $xlsx_export
   *   The XLSX source plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, XlsxSourceManager $xlsx_source, XlsxExportManager $xlsx_export) {
    parent::__construct($entity_type, $storage);
    $this->xlsxSourceManager = $xlsx_source;
    $this->xlsxExportManager = $xlsx_export;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.xlsx_source'),
      $container->get('plugin.manager.xlsx_export')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Name');
    $header['source'] = $this->t('Source');
    $header['export'] = $this->t('Export As');
    $header['export_only'] = $this->t('Export Only');
    $header['cron'] = $this->t('Cron Import');
    $header['last_import'] = $this->t('Last Import');
    $header['last_export'] = $this->t('Last Export');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $mapping = $entity->getMapping();
    $plugin = $this->xlsxSourceManager->createInstance($mapping['source']);
    $ajax_attributes = [
      'class' => ['use-ajax'],
      'data-dialog-type' => 'modal',
      'data-dialog-options' => Json::encode(['width' => 700]),
    ];
    $export_plugins = [];
    if (!empty($mapping['export'])) {
      foreach ($mapping['export'] as $plugin_id) {
        if ($export_plugin = $this->xlsxExportManager->createInstance($plugin_id)) {
          $export_plugins[] = $export_plugin->getName();
        }
      }
    }
    $row['label'] = Link::createFromRoute(
      $entity->label(),
      'entity.xlsx.import',
      ['xlsx' => $entity->id()],
      ['attributes' => $ajax_attributes]
    );
    $row['source'] = $plugin->getName();
    $row['export'] = join(', ', $export_plugins);
    $row['export_only'] = !empty($mapping['export_only']) ? $this->t('Yes') : $this->t('No');
    $row['cron'] = $plugin->isCron() ? $this->t('Yes') : $this->t('No');
    $row['last_import'] = $entity->getLastImport();
    $row['last_export'] = $entity->getLastExport();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $ajax_attributes = [
      'class' => ['use-ajax'],
      'data-dialog-type' => 'modal',
      'data-dialog-options' => Json::encode(['width' => 700]),
    ];
    if (!$entity->isExportOnly()) {
      $operations['import'] = [
        'title' => $this->t('Import'),
        'url' => $this->ensureDestination($entity->toUrl('import')),
        'attributes' => $ajax_attributes,
      ];
    }
    $operations['export'] = [
      'title' => $this->t('Export'),
      'url' => $this->ensureDestination($entity->toUrl('export')),
      'attributes' => $ajax_attributes,
    ];
    $operations['edit'] = [
      'title' => $this->t('Edit'),
      'url' => $entity->toUrl('edit'),
      'attributes' => $ajax_attributes,
    ];
    $operations['purge'] = [
      'title' => $this->t('Purge'),
      'url' => $this->ensureDestination($entity->toUrl('purge')),
      'attributes' => $ajax_attributes,
    ];
    $operations['delete'] = [
      'title' => $this->t('Delete'),
      'url' => $this->ensureDestination($entity->toUrl('delete')),
      'attributes' => $ajax_attributes,
    ];
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $config = \Drupal::config(LicenseForm::CONFIG_NAME);
    $build = parent::render();
    if (empty($config->get('license_email')) || empty($config->get('license_number'))) {
      $build['license_info'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="xlsx-msg require-license"><strong>Please <a href="{{ link }}" class="use-ajax"  data-dialog-type="modal" data-dialog-options="{&quot;width&quot;:&quot;525&quot;}" data-drupal-link-system-path="{{ link|trim(\'/\', \'left\') }}">provide license information</a> in order to be able to get module updates.</strong></div>',
        '#context' => [
          'link' => Url::fromRoute('xlsx.license')->toString(),
        ],
        '#weight' => -200,
        '#attached' => [
          'library' => ['xlsx/xlsx.admin']
        ],
      ];
      $build['contact_developer'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="xlsx-info-msg">If you have questions or you would like customizations please <a href="https://downloads.minnur.com/contact-developer" target="_blank">contact developer</a>.</div>',
        '#weight' => 100,
        '#attached' => [
          'library' => ['xlsx/xlsx.admin']
        ],
      ];
    }
    return $build;
  }

}
