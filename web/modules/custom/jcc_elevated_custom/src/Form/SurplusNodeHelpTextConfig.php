<?php

namespace Drupal\jcc_elevated_custom\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * JCC Elevated Surplus node custom text.
 */
class SurplusNodeHelpTextConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jcc_elevated.node_help_text'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_elevated_custom_node_help_text_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jcc_elevated_custom.node_help_text');

    $form['node_help_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Node Help Text'),
      '#default_value' => $config->get('node_help_text') ?? '',
      '#description' => $this->t('Text displayed at the top of the Surplus Materials node add/edit form. HTML is allowed.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('jcc_elevated_custom.node_help_text')
      ->set('node_help_text', $form_state->getValue('node_help_text'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
