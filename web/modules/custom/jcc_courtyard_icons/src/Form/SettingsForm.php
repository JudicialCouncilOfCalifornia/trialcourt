<?php

namespace Drupal\jcc_courtyard_icons\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure JCC Courtyard Icons settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_courtyard_icons_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jcc_courtyard_icons.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['icons_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to icons.svg file, relative to Drupal root'),
      '#description' => $this->t('path/to/icons.svg'),
      '#default_value' => $this->config('jcc_courtyard_icons.settings')->get('icons_path'),
    ];

    $form['icon_sets'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Icon set prefixes for grouping icons. i.e. fa, line-white, line-dark'),
      '#description' => $this->t('One prefix per line. Expects SVG Sprite icon names in this format: icon-[set]-[name]  i.e. icon-line-white-circle-information'),
      '#default_value' => $this->config('jcc_courtyard_icons.settings')->get('icon_sets'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $icons_path = $form_state->getValue('icons_path');
    $drupal_path = drupal_get_path('core', NULL) . '/..';
    if (!file_exists("$drupal_path/$icons_path")) {
      $form_state->setErrorByName('icons_path', $this->t('The path is not valid or file does not exist.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('jcc_courtyard_icons.settings')
      ->set('icons_path', $form_state->getValue('icons_path'))
      ->save();
    $this->config('jcc_courtyard_icons.settings')
      ->set('icon_sets', $form_state->getValue('icon_sets'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
