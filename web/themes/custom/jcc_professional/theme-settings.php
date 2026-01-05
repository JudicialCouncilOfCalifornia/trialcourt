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
  $form['social']['instagram'] = [
    '#type' => 'textfield',
    '#title' => t('Instagram'),
    '#default_value' => theme_get_setting('instagram'),
    '#placeholder' => 'https://www.instagram.com/[name]',
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

  $form['global']['hide_was_this_helpful'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Hide feedback widget'),
    '#default_value' => theme_get_setting('hide_was_this_helpful'),
    '#description'   => t("Hide 'Was this helpful' widget"),
  ];

  $form['global']['hide_translation'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Hide translation'),
    '#default_value' => theme_get_setting('hide_translation'),
    '#description'   => t("Hide translation dropdown from header."),
  ];

  // Edit no search results message.
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

  // Granicus integration.
  $form['granicus'] = [
    '#type' => 'details',
    '#title' => t('Granicus'),
    '#collapsed'  => TRUE,
  ];
  $granicus_event_type = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->load(theme_get_setting('granicus_event_type'));
  $form['granicus']['granicus_event_type'] = [
    '#type' => 'entity_autocomplete',
    '#title' => t('Event type tag'),
    '#target_type' => 'taxonomy_term',
    '#selection_settings' => [
      'target_bundles' => ['event_type'],
    ],
    '#default_value' => $granicus_event_type,
  ];
  $form['granicus']['granicus_english_captions'] = [
    '#type' => 'textfield',
    '#title' => t('English Captions'),
    '#default_value' => theme_get_setting('granicus_english_captions'),
  ];
  $form['granicus']['granicus_spanish_captions'] = [
    '#type' => 'textfield',
    '#title' => t('Español Captions'),
    '#default_value' => theme_get_setting('granicus_spanish_captions'),
  ];
  $form['granicus']['granicus_status_override'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Always display as a live broadcast?'),
    '#default_value' => theme_get_setting('granicus_status_override'),
  ];
}
