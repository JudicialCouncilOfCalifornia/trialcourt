<?php

namespace Drupal\xlsx\Form;

use Drupal\xlsx\Form\Mapping\BaseForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Export XLSX form controller.
 *
 * @ingroup xlsx
 */
class ExportXlsxForm extends BaseForm {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'xlsx_export_form';
  }

  /**
   * Title callback.
   */
  public function getTitle() {
    $xlsx = \Drupal::routeMatch()->getParameter('xlsx');
    return $this->t('Export %name', ['%name' => $xlsx->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $xlsx = NULL) {
    $form = parent::buildForm($form, $form_state);
    $form_state->setStorage(['xlsx' => $xlsx]);
    $formats = [];
    foreach ($xlsx->getExportPlugins() as $export_plugin_id) {
      $xlsx_export = $this->xlsxExportManager->createInstance($export_plugin_id);
      if ($xlsx_export->classExists()) {
        $formats[$xlsx_export->getPluginId()] = $xlsx_export->getName();
      }
    }
    if (!empty($formats)) {
      $default_value = array_keys($formats);
      $form['format'] = [
        '#type' => 'radios',
        '#title' => $this->t('Export As'),
        '#options' => $formats,
        '#default_value' => !empty($default_value[0]) ? $default_value[0] : NULL,
        '#required' => TRUE,
      ];
      $form['password_protected'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Export as password protected Zip file'),
      ];
      $form['password'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Set ZIP password'),
        '#description' => $this->t('If you are using Mac please try <strong>use The Unarchiver</strong> app, default Archive Utility app might not accept this password to unzip.'),
        '#size' => 25,
        '#attributes' => [
          'autocomplete' => 'off',
        ],
        '#states' => [
          'visible' => [
            'input[name="password_protected"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $export_plugins = $xlsx->getExportPlugins();
      foreach ($export_plugins as $plugin_id) {
        if ($xlsx_export = $this->xlsxExportManager->createInstance($plugin_id)) {
          if ($xlsx_export->classExists()) {
            $export_form = $xlsx_export->exportForm($form, $form_state, $xlsx);
            $form = array_merge($form, $export_form);
          }
        }
      }
      $remote_plugins = [];
      foreach ($this->xlsxRemoteManager->getDefinitions() as $id => $plugin) {
        $remote_plugin = $this->xlsxRemoteManager->createInstance($plugin['id']);
        if ($remote_plugin->classExists()) {
          $remote_plugins[$id] = $remote_plugin->getName();
        }
      }
      if (count($remote_plugins) > 1) {
        $form['remote'] = [
          '#type' => 'radios',
          '#title' => $this->t('Export Destination'),
          '#options' => $remote_plugins,
          '#default_value' => 'browser',
          '#required' => TRUE,
        ];
      }
      else {
        $form['remote'] = [
          '#type' => 'hidden',
          '#value' => 'browser',
        ];
      }
    }
    $form['actions']['submit']['#value'] = $this->t('Run Export');
    $form['actions']['cancel']['#attributes']['class'] = ['button', 'dialog-cancel'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('password_protected') && empty($form_state->getValue('password'))) {
      $form_state->setErrorByName('password', $this->t('Please provide password.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $xlsx_export = $this->xlsxExportManager->createInstance($form_state->getValue('format'));
    $xlsx_export->submitExportForm($form, $form_state, $storage['xlsx']);
  }

}
