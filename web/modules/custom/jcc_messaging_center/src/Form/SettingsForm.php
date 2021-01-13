<?php

namespace Drupal\jcc_messaging_center\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings Form for MyEmma webform field.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'weform_myemma_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'jcc_subscriptions.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jcc_subscriptions.settings');

    $form_state->setCached(FALSE);

    $form['newslink_digest_group'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Newslink Digest Group'),
      '#default_value' => $config->get('newslink_digest_group'),
    ];

    $form['newslink_digest_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Digest Sent Time'),
      '#default_value' => $config->get('newslink_digest_time') ?? '17:00',
    ];

    $form['newslink_digest_debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug NewsLink Digest'),
      '#description' => $this->t('Digest will be mailed every cron run.'),
      '#default_value' => $config->get('newslink_digest_debug'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $config \Drupal\Core\Config\Config */
    $config = $this->configFactory->getEditable('jcc_subscriptions.settings');

    $config->set('newslink_digest_group', $form_state->getValue('newslink_digest_group'))->save();
    $config->set('newslink_digest_time', $form_state->getValue('newslink_digest_time'))->save();
    $config->set('newslink_digest_debug', $form_state->getValue('newslink_digest_debug'))->save();

    parent::submitForm($form, $form_state);
  }

}
