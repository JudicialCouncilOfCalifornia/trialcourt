<?php

namespace Drupal\xlsx\Form\Mapping;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class NewForm.
 *
 * @ingroup xlsx
 */
class NewForm extends BaseForm {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'xlsx_new_mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Make sure we cleanup everything and start over whenever we visit new mapping form page.
    $this->deleteStore();
    $form = parent::buildForm($form, $form_state);
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('This name will be displayed on the mapping listing page and also will be used to generate export file.'),
      '#required' => TRUE,
    ];
    $source_plugins = [];
    foreach ($this->xlsxSourceManager->getDefinitions() as $id => $plugin) {
      $source_plugin = $this->xlsxSourceManager->createInstance($plugin['id']);
      if ($source_plugin->classExists()) {
        $source_plugins[$id] = $source_plugin->getName();
      }
    }
    $form['source'] = [
      '#title' => $this->t('Source'),
      '#type' => 'radios',
      '#description' => $this->t('Choose source type for the data mapping.'),
      '#options' => $source_plugins,
      '#default_value' => 'xlsx_file',
      '#required' => TRUE,
    ];
    $form['export_only'] = [
      '#title' => $this->t('Make this mapping export only'),
      '#type' => 'checkbox',
      '#description' => $this->t('When checked the mapping will be used only for data export.'),
      '#default_value' => FALSE,
    ];
    $form['actions']['submit']['#value'] = $this->t('Next');
    $form['actions']['cancel']['#attributes']['class'] = ['button', 'dialog-cancel'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name');
    // Make sure no special characters and support for international characters.
    if (!preg_match('/^[a-zA-Z0-9 \p{L}]+$/ui', $name) || strlen(trim($name)) < 3) {
      $form_state->setErrorByName('name', $this->t('Please provide a valid name. The name should contain letters and/or numbers and should be at least 3 characters long.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('name', $form_state->getValue('name'));
    $this->store->set('source', $form_state->getValue('source'));
    $this->store->set('export_only', $form_state->getValue('export_only'));
    $form_state->setRedirect('xlsx.new.source', [
      'xlsx_source' => $this->pluginToUrlParam($form_state->getValue('source'))
    ]);
  }

}
