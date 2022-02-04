<?php

namespace Drupal\xlsx\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the XLSX entity.
 *
 * @ConfigEntityType(
 *   id = "xlsx",
 *   label = @Translation("Spreadsheet"),
 *   handlers = {
 *     "list_builder" = "Drupal\xlsx\Controller\XlsxListBuilder",
 *   },
 *   admin_permission = "administer xlsx maping",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "mapping",
 *   },
 *   links = {
 *     "import" = "/admin/structure/xlsx/{xlsx}/import",
 *     "export" = "/admin/structure/xlsx/{xlsx}/export",
 *     "new" = "/admin/structure/xlsx/new",
 *     "purge" = "/admin/structure/xlsx/{xlsx}/purge",
 *     "edit" = "/admin/structure/xlsx/{xlsx}/edit",
 *     "delete" = "/admin/structure/xlsx/{xlsx}/delete",
 *   }
 * )
 */
class XlsxEntity extends ConfigEntityBase implements XlsxEntityInterface {
  use StringTranslationTrait;

  /**
   * The name (plugin ID) of the tour.
   *
   * @var string
   */
  protected $id;

  /**
   * The module which this XLSX mapping is assigned to.
   *
   * @var string
   */
  protected $module;

  /**
   * The label of the XLSX.
   *
   * @var string
   */
  protected $label;

  /**
   * The array of plugin config, only used for export and to populate the $xlsxCollection.
   *
   * @var array
   */
  protected $mapping = [];

  /**
   * {@inheritdoc}
   */
  public function getMapping() {
    return $this->get('mapping');
  }

  /**
   * {@inheritdoc}
   */
  public function setMapping($mapping) {
    $this->set('mapping', $mapping);
  }

  /**
   * {@inheritdoc}
   */
  public function getSourcePlugin() {
    $mapping = $this->getMapping();
    return $mapping['source'];
  }

  /**
   * {@inheritdoc}
   */
  public function getExportPlugins() {
    $mapping = $this->getMapping();
    return $mapping['export'];
  }

  /**
   * {@inheritdoc}
   */
  public function isExportOnly() {
    $mapping = $this->getMapping();
    return (bool) $mapping['export_only'];
  }

  /**
   * {@inheritdoc}
   */
  public function isCron() {
    $mapping = $this->getMapping();
    return !empty($mapping['cron']);
  }

  /**
   * {@inheritdoc}
   */
  public function shouldExecuteOnCron() {
    $mapping = $this->getMapping();
    if ($this->isCron()) {
      $inteval = (int) $mapping['cron_frequency'];
      if (empty($interval)) {
        return TRUE;
      }
      else {
        $last_cron_run = \Drupal::state()->get('xlsx.last_cron_run');
        $current_time = \Drupal::time()->getCurrentTime();
        if (empty($last_cron_run[$this->id()]) || ($current_time >= $last_cron_run[$this->id()])) {
          $last_cron_run[$this->id()] = $current_time + $interval;
          \Drupal::state()->set('xlsx.last_cron_run', $last_cron_run);
          return TRUE;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setLastImport() {
    $times = \Drupal::state()->get('xlsx.import');
    $times[$this->id()] = \Drupal::time()->getCurrentTime();
    \Drupal::state()->set('xlsx.import', $times);
  }

  /**
   * {@inheritdoc}
   */
  public function getLastImport() {
    $times = \Drupal::state()->get('xlsx.import');
    return !empty($times[$this->id()]) ? \Drupal::service('date.formatter')->format($times[$this->id()]) : $this->t('N/A');
  }

  /**
   * {@inheritdoc}
   */
  public function setLastExport() {
    $times = \Drupal::state()->get('xlsx.export');
    $times[$this->id()] = \Drupal::time()->getCurrentTime();
    \Drupal::state()->set('xlsx.export', $times);
  }

  /**
   * {@inheritdoc}
   */
  public function getLastExport() {
    $times = \Drupal::state()->get('xlsx.export');
    return !empty($times[$this->id()]) ? \Drupal::service('date.formatter')->format($times[$this->id()]) : $this->t('N/A');
  }

}
