<?php

namespace Drupal\jcc_messaging_center\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings Form for Messaging center.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_messaging_center_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'jcc_messaging_center.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jcc_messaging_center.settings');

    $types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();

    $default_value = [];
    if($config->get('messaging_content_types') != NULL){
      $default_value = $config->get('messaging_content_types');
    }

    $types_options = [];
    foreach ($types as $node_type) {
      $types_options[$node_type->id()] = $node_type->label();
    }

    $form_state->setCached(FALSE);

    $form['messaging_content_types'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Content types available for email notification'),
      '#options' => $types_options,
      '#default_value' => $default_value,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $config \Drupal\Core\Config\Config */
    $config = $this->configFactory->getEditable('jcc_messaging_center.settings');

    $config->set('messaging_content_types', $form_state->getValue('messaging_content_types'))->save();

    parent::submitForm($form, $form_state);
  }

}
