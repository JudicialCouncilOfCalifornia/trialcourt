<?php

/**
 * @file
 * Theme settings.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function jcc_professional_form_system_theme_settings_alter(&$form, FormStateInterface $form_state, $form_id = NULL) {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

  $form['theme'] = [
    '#type' => 'details',
    '#title' => t('Theme settings'),
    '#collapsed'  => TRUE,
  ];

  $form['theme']['scheme'] = [
    '#type' => "select",
    '#title' => t('Scheme'),
    '#options' => [
      'base' => t('Base'),
      'local' => t('Local'),
      'pro' => t('Pro'),
    ],
    '#default_value' => theme_get_setting('scheme'),
    '#description' => t('A scheme sets the general look and feel of the site. You still have the same building blocks from the component library, but global styles such as color, spacing, etc., can vary according to the selected scheme.'),
  ];

  $form['theme']['header_footer_variant'] = [
    '#type' => 'select',
    '#title' => t('Header/Footer Variant'),
    '#options' => [
      'default' => t('Default'),
      'alt' => t('Alt'),
    ],
    '#default_value' => theme_get_setting('header_footer_variant'),
    '#description' => t(
      'Set the patternlab variant for the <a target="_blank" href=":header">header</a> and <a target="_blank" href=":footer">footer</a>.',
      [
        ':header' => 'http://patternlab.courts.ca.gov/2.x/public/?p=viewall-organisms-header',
        ':footer' => 'http://patternlab.courts.ca.gov/2.x/public/?p=viewall-organisms-footer',
      ]
    ),
  ];

  $form['theme']['site_name_first'] = [
    '#type' => 'textfield',
    '#title' => t("Site Name: First"),
    '#placeholder' => t("Superior Court of California"),
    '#default_value' => theme_get_setting('site_name_first'),
    '#description' => t('Site name is split into two parts to improve presentation on small screens.'),
  ];

  $form['theme']['site_name_second'] = [
    '#type' => 'textfield',
    '#title' => t("Site Name: Second"),
    '#placeholder' => t("County of ..."),
    '#default_value' => theme_get_setting('site_name_second'),
    '#description' => t('Site name is split into two parts to improve presentation on small screens.'),
  ];

  $form['theme']['hat_shoe_text'] = [
    '#type' => 'textfield',
    '#title' => t("Hat/Shoe Text"),
    '#placeholder' => t("Judicial Branch of California"),
    '#default_value' => theme_get_setting('hat_shoe_text'),
    '#description' => t("A link that shows at the very top and bottom."),
  ];

  $form['theme']['hat_shoe_url'] = [
    '#type' => 'url',
    '#title' => t("Hat/Shoe URL"),
    '#placeholder' => "https://www.courts.ca.gov",
    '#default_value' => theme_get_setting('hat_shoe_url'),
    '#description' => t("A link that shows at the very top and bottom."),
  ];

  $form['theme']['site_name_second'] = [
    '#type' => 'textfield',
    '#title' => t("Site Name: Second"),
    '#placeholder' => t("County of ..."),
    '#default_value' => theme_get_setting('site_name_second'),
    '#description' => t('Site name is split into two parts to improve presentation on small screens.'),
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

  // BEGIN: Special header body/feature.
  $form['special'] = [
    '#type' => 'details',
    '#title' => t('Special'),
    '#collapsed'  => TRUE,
  ];
  $header_body_value = '';
  $header_body_format = 'full_html';
  $header_body = theme_get_setting('header_body');
  if (isset($header_body)) {
    $header_body_value = $header_body['value'];
    $header_body_format = $header_body['format'];
  }

  $form['special']['header_body'] = [
    '#type' => 'text_format',
    '#title' => 'Header Body',
    '#description' => t('For inserting special content or features into header area (e.g. Granicus live cast embed).'),
    '#default_value' => $header_body_value,
    '#format' => $header_body_format,
  ];
}
