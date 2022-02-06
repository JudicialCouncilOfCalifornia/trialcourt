<?php

namespace Drupal\xlsx\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface XlsxExport plugins.
 */
interface XlsxExportInterface extends PluginInspectionInterface {

  /**
   * Return the name of the XLSX export plugin.
   *
   * @return string
   */
  public function getName();

  /**
   * Return description for the XLSX export plugin.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Get XLSX supported sources.
   */
  public function getSourceTypes();

  /**
   * Get export data type.
   */
  public function getDataType();

  /**
   * Get class to check availability.
   */
  public function classExists();

  /**
   * Build export data form.
   */
  public function exportForm(array $form, FormStateInterface $form_state, $xlsx = NULL);

  /**
   * Process export.
   */
  public function submitExportForm(array &$form, FormStateInterface $form_state);

}
