<?php

/**
 * @file
 * Theme settings.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function jcc_components_form_system_theme_settings_alter(&$form, FormStateInterface $form_state, $form_id = NULL) {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

  $form['scheme'] = [
    '#type' => "select",
    '#title' => t('Scheme'),
    '#options' => [
      'base' => t('Base'),
      'local' => t('Local'),
    ],
    '#default_value' => theme_get_setting('scheme'),
    '#description' => t('A scheme sets the general look and feel of the site. You still have the same building blocks from the component library, but global styles such as color, spacing, etc., can vary according to the selected scheme.'),
  ];

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
  $form['theme'] = [
    '#type' => 'details',
    '#title' => t('Theme settings'),
    '#collapsed'  => TRUE,
  ];
  $form['theme']['header_extended'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Extended Header'),
    '#default_value' => theme_get_setting('header_extended'),
    '#description'   => t('Set the patternlab <a target="_blank" href=":extended">extended header variant</a> instead of the <a target="_blank" href=":default">default one</a>.', [':extended' => 'http://patternlab.courts.ca.gov/?p=organisms-header-base-extended', ':default' => 'http://patternlab.courts.ca.gov/?p=organisms-header-base']),
  ];
  $form['theme']['mega_menu'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Mega menu'),
    '#default_value' => theme_get_setting('mega_menu'),
    '#description'   => t('Set the patternlab <a target="_blank" href=":mega">Mega menu variant</a>. (This will only apply if the header is set to extended)', [':mega' => 'http://patternlab.courts.ca.gov/?p=organisms-header-base-extended-mega']),
  ];
  $form['theme']['footer_extended'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Extended footer'),
    '#default_value' => theme_get_setting('footer_extended'),
    '#description'   => t('Set the patternlab <a target="_blank" href=":extended_footer">Extended footer variant</a> instead of the <a target="_blank" href=":default_footer">default one</a>.', [':extended_footer' => 'http://patternlab.courts.ca.gov/?p=organisms-footer-tall', ':default_footer' => 'http://patternlab.courts.ca.gov/?p=organisms-footer-base']),
  ];
  $form['theme']['footer_extended_message'] = [
    '#type'          => 'textfield',
    '#title'         => t('Custom footer message'),
    '#default_value' => theme_get_setting('footer_extended_message'),
    '#description'   => t('Set the footer message in the second part of the footer.'),
  ];
  $form['theme']['color_inverted'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Inverted Color'),
    '#default_value' => theme_get_setting('color_inverted'),
    '#description'   => t('Set the patternlab inverted color variant for the <a target="_blank" href=":inverted_header">header</a> and the <a target="_blank" href=":inverted_footer">footer</a>.', [':inverted_header' => 'http://patternlab.courts.ca.gov/?p=organisms-header-base-inverted', ':inverted_footer' => 'http://patternlab.courts.ca.gov/?p=organisms-footer-base-inverted']),
  ];
}
