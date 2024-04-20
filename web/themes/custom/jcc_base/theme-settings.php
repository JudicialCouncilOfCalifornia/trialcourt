<?php

/**
 * @file
 * Theme settings.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function jcc_base_form_system_theme_settings_alter(&$form, FormStateInterface $form_state, $form_id = NULL) {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }
  $form['global'] = [
    '#type' => 'details',
    '#title' => t('Global settings'),
    '#open'  => FALSE,
  ];
  $form['global']['hide_translation'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Hide translation'),
    '#default_value' => theme_get_setting('hide_translation'),
    '#description'   => t("Hide translation dropdown from header."),
  ];
  // BEGIN Edit no search results message.
  $form['global']['no_search_results'] = [
    '#type' => 'details',
    '#title' => t('No search results message'),
    '#collapsed'  => TRUE,
  ];
  $form['global']['no_search_results']['no_search_results_heading'] = [
    '#type'          => 'textfield',
    '#title'         => t('Personalized heading'),
    '#default_value' => theme_get_setting('no_search_results_heading'),
  ];
  $no_results_msg = theme_get_setting('no_search_results_message');
  $form['global']['no_search_results']['no_search_results_message'] = [
    '#type'          => 'text_format',
    '#format'        => $no_results_msg ? $no_results_msg['format'] : 'snippet',
    '#title'         => t('Personalized message'),
    '#default_value' => $no_results_msg ? $no_results_msg['value'] : '',
  ];
  $form['theme'] = [
    '#type' => 'details',
    '#title' => t('Theme settings'),
    '#open'  => TRUE,
  ];
  $form['theme']['site_entity_type'] = [
    '#type' => 'select',
    '#title' => t('What type of judicial site is this?'),
    '#default_value' => theme_get_setting('site_entity_type'),
    '#description'   => t("The judicial site type can enable specific features. For example, ensures the correct footer boilerplate branding appears."),
    '#options' => [
      '' => t('General'),
      'superior' => t('Superior Court'),
    ],
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
  // BEGIN Footer extension settings.
  $form['theme']['footer_extended_settings'] = [
    '#type' => 'container',
    '#states' => [
      // Hide the extended footer settings when using basic setting.
      'invisible' => [
        'input[name="footer_extended"]' => ['checked' => FALSE],
      ],
    ],
  ];
  $form['theme']['footer_extended_settings']['footer_extended_message'] = [
    '#type'          => 'textfield',
    '#title'         => t('Optional site name adjustment'),
    '#default_value' => theme_get_setting('footer_extended_message'),
    '#description'   => t('Adjust the site name if a variant is needed from the <a target="_blank" href=":basic_site_settings">basic site setting</a>.', [':basic_site_settings' => '/admin/config/system/site-information']),
  ];
  // END.
  $form['theme']['color_inverted'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Inverted Color'),
    '#default_value' => theme_get_setting('color_inverted'),
    '#description'   => t('Set the patternlab inverted color variant for the <a target="_blank" href=":inverted_header">header</a> and the <a target="_blank" href=":inverted_footer">footer</a>.', [':inverted_header' => 'http://patternlab.courts.ca.gov/?p=organisms-header-base-inverted', ':inverted_footer' => 'http://patternlab.courts.ca.gov/?p=organisms-footer-base-inverted']),
  ];

  $form['social'] = [
    '#type' => 'details',
    '#title' => t('Social Links'),
    '#open'  => FALSE,
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
}
