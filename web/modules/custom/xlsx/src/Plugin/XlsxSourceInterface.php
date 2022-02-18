<?php

namespace Drupal\xlsx\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface XlsxSource plugins.
 */
interface XlsxSourceInterface extends PluginInspectionInterface {

  /**
   * Return the name of the XLSX source plugin.
   *
   * @return string
   */
  public function getName();

  /**
   * Return description for the XLSX source plugin.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Get XLSX file worksheets.
   */
  public function getWorksheets();

  /**
   * Build source upload form.
   */
  public function sourceForm(array $form, FormStateInterface $form_state);

  /**
   * Process uploaded file.
   */
  public function submitSourceForm(array &$form, FormStateInterface $form_state);

  /**
   * Build import data form.
   */
  public function importForm(array $form, FormStateInterface $form_state);

  /**
   * Process uploaded file (import data).
   */
  public function submitImportForm(array &$form, FormStateInterface $form_state);

  /**
   * Get class to check availability.
   */
  public function classExists();

}
