<?php

namespace Drupal\xlsx\Form\Mapping;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class EditForm.
 *
 * @ingroup xlsx
 */
class EditForm extends BaseForm {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'xlsx_edit_mapping_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $xlsx = NULL) {
    // Load mapping data and add it to tmp storage
    $this->populateTmp($xlsx);
    $form = parent::buildForm($form, $form_state);
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('This name will be displayed on the mapping listing page and also will be used to generate export file.'),
      '#default_value' => $this->store->get('name'),
      '#required' => TRUE,
    ];
    $plugin = $this->xlsxSourceManager->createInstance($this->store->get('source'));
    $form['source'] = [
      '#type' => 'item',
      '#title' => $this->t('Source'),
      '#markup' => $plugin->getName(),
    ];
    if ($plugin->isCron()) {
      $form['cron'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Run this import on cron (Automatic imports)'),
        '#default_value' => $this->store->get('cron'),
      ];
      $options = [3600, 10800, 21600, 43200, 86400, 604800];
      $form['cron_frequency'] = [
        '#type' => 'select',
        '#title' => $this->t('Frequency'),
        '#options' => [0 => $this->t('On each cron run')] + array_map([\Drupal::service('date.formatter'), 'formatInterval'], array_combine($options, $options)),
        '#description' => $this->t('How often perform automatic imports (this process adds items to the Drupal Queue API). This configuration option also depends on how Cron is configured in the system.'),
        '#states' => [
          'visible' => [':input[name="cron"]' => ['checked' => TRUE]]
        ],
        '#default_value' => $this->store->get('cron_frequency'),
      ];
    }
    $form['export_only'] = [
      '#title' => $this->t('Make this mapping export only'),
      '#description' => $this->t('When checked the mapping will be used only for data export.'),
      '#type' => 'checkbox',
      '#default_value' => (bool) $this->store->get('export_only'),
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
    $this->store->set('cron', $form_state->getValue('cron'));
    $this->store->set('cron_frequency', $form_state->getValue('cron_frequency'));
    $this->store->set('export_only', $form_state->getValue('export_only'));
    $form_state->setRedirect('xlsx.new.entity_map', [
      'xlsx_source' => $this->pluginToUrlParam($this->store->get('source'))
    ]);
  }

}
