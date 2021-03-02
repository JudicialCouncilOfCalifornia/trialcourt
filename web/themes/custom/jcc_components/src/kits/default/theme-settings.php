<?php

/**
 * @file
 * Theme Settings.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function JCC_COMPONENTS_SUBTHEME_MACHINE_NAME_form_system_theme_settings_alter(&$form, FormStateInterface $form_state, $form_id = NULL) {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

  $form['social'] = [
    '#type' => 'details',
    '#title' => t('Social Links'),
    '#collapsed'  => TRUE,
  ];
  $form['social']['email'] = [
    '#type' => 'textfield',
    '#title' => t('Email'),
    '#default_value' => theme_get_setting('email'),
    '#placeholder' => 'https://newsroom.courts.ca.gov/alerts',
  ];
  $form['social']['facebook'] = [
    '#type' => 'textfield',
    '#title' => t('Facebook'),
    '#default_value' => theme_get_setting('facebook'),
    '#placeholder' => 'https://www.facebook.com/[name]/',
  ];
  $form['social']['flickr'] = [
    '#type' => 'textfield',
    '#title' => t('Flickr'),
    '#default_value' => theme_get_setting('flickr'),
    '#placeholder' => 'https://www.flickr.com/photos/[name]/sets/',
  ];
  $form['social']['linkedin'] = [
    '#type' => 'textfield',
    '#title' => t('LinkedIn'),
    '#default_value' => theme_get_setting('linkedin'),
    '#placeholder' => 'https://www.linkedin.com/company/[name]/',
  ];
  $form['social']['rss'] = [
    '#type' => 'textfield',
    '#title' => t('RSS'),
    '#default_value' => theme_get_setting('rss'),
    '#placeholder' => 'https://newsroom.courts.ca.gov/rss',
  ];
  $form['social']['twitter'] = [
    '#type' => 'textfield',
    '#title' => t('Twitter'),
    '#default_value' => theme_get_setting('twitter'),
    '#placeholder' => 'https://twitter.com/[name]',
  ];
  $form['social']['youtube'] = [
    '#type' => 'textfield',
    '#title' => t('YouTube'),
    '#default_value' => theme_get_setting('youtube'),
    '#placeholder' => 'https://www.youtube.com/user/[name]',
  ];
  $form['global'] = [
    '#type' => 'details',
    '#title' => t('Global settings'),
    '#collapsed'  => TRUE,
  ];
  $form['global']['hide_translation'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Hide translation'),
    '#default_value' => theme_get_setting('hide_translation'),
    '#description'   => t("Hide translation dropdown from header."),
  ];
}
