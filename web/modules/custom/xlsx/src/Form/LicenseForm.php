<?php

namespace Drupal\xlsx\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LicenseForm.
 *
 * @ingroup xlsx
 */
class LicenseForm extends ConfigFormBase {

  const CONFIG_NAME = 'xlsx.config';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [static::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xlsx_premium_license';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    // Get the working configuration.
    $config = $this->config(static::CONFIG_NAME);

    $form['#attached']['library'][] = 'xlsx/xlsx.admin';

    if (!\Drupal::moduleHandler()->moduleExists('update_premium')) {
      $form['update_premium'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="xlsx-msg"><strong>In order to be able to pull available updates for premium modules please install <em>Premium Updates</em> module and provide license information. <a href="https://www.drupal.org/project/update_premium" class="button button--primary" target="_blank">Download Premium Updates</a></strong></div>',
        '#weight' => -200,
      ];
    }
    $form['info'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="xlsx-info-msg">If you have questions or you would like customizations please <a href="https://downloads.minnur.com/contact-developer" target="_blank">contact developer</a>.</div>',
      '#weight' => 100,
    ];

    $form['license_email'] = [
      '#type'          => 'email',
      '#title'         => $this->t('License Email'),
      '#description'   => $this->t('Email address that you used to purchase the module.'),
      '#default_value' => $config->get('license_email'),
      '#required'      => TRUE,
    ];

    $form['license_number'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('License Number'),
      '#description'   => $this->t('You may find this license number in your purchase confirmation email.'),
      '#default_value' => $config->get('license_number'),
      '#required'      => TRUE,
    ];

    $form['actions']['submit']['#value'] = $this->t('Register License');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config(static::CONFIG_NAME)
      ->set('license_email', $form_state->getValue('license_email'))
      ->set('license_number', $form_state->getValue('license_number'))
      ->save();
  }

}
