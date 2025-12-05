<?php

/**
 * @file
 * Theme settings.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function jcc_elevated_form_system_theme_settings_alter(&$form, FormStateInterface $form_state, $form_id = NULL) {
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

  $form['global']['show_google_translate'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Show Google translator'),
    '#default_value' => theme_get_setting('show_google_translate'),
    '#description'   => t("Show Google translation dropdown in header."),
  ];

  // Block Web search engine indexing.
  $bsefi_label = t('Block Web search engine indexing');
  $bsefi_desc = t('Prevents any content - node and media - from appearing in Web search results. To prevent search engines from crawling this site, <a href="/admin/config/search/robotstxt">adjust the instructions</a> for the site\'s robots.txt.');
  $form['global']['block_search_engine_indexing'] = [
    '#type'          => 'checkbox',
    '#title'         => $bsefi_label,
    '#default_value' => theme_get_setting('block_search_engine_indexing'),
    '#description'   => $bsefi_desc,
  ];

  $form['global']['news_default_seal'] = [
    '#type' => 'textfield',
    '#title' => t('News Default Seal Path'),
    '#default_value' => theme_get_setting('news_default_seal'),
    '#description' => t('Enter the path to the default branch seal image(themes/custom/jcc_elevated/images/news-default-seal.svg).'),
  ];

  // BEGIN Edit no search results message.
  $form['global']['no_search_results'] = [
    '#type' => 'fieldset',
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

  /*
   * Default landing page values for feature use.
   */
  $form['default_landing_pages'] = [
    '#type' => 'details',
    '#title' => t('Content & media landing pages'),
    '#description' => t('Select the page within the information architecture as the default landing for content or media. Used where applicable such as breadcrumb substitution, page with embedded view reference, etc.'),
    '#collapsed'  => TRUE,
  ];

  // Get all node bundles.
  $bundle_info_service = \Drupal::service('entity_type.bundle.info');
  $node_bundles = $bundle_info_service->getBundleInfo('node');
  $media_bundles = $bundle_info_service->getBundleInfo('media');
  $bundles = array_merge($node_bundles, $media_bundles);
  ksort($bundles);

  if (\Drupal::service('module_handler')->moduleExists('jcc_elevated_sections')) {
    // Create landing set per section.
    $vocab_id = 'jcc_sections';
    $sections = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocab_id);
    foreach ($sections as $section) {
      $section_id = $section->jcc_section_machine_name;
      $section_name = $section->name;

      $form['default_landing_pages'][$section_id] = [
        '#type' => 'details',
        '#title' => $section_name,
      ];

      $form['default_landing_pages'][$section_id] = array_merge($form['default_landing_pages'][$section_id], __jcc_landingpage_options_set($bundles, $section_id));
    }
  }
  else {
    // Default single landing set.
    $form['default_landing_pages'] = array_merge($form['default_landing_pages'], __jcc_landingpage_options_set($bundles, NULL));
  }
}

/**
 * Helper function to build landing page option field.
 *
 * @return array
 *   Form field setup.
 */
function __jcc_landingpage_option_generator(string $title, string $type_field_id) {
  $form = [
    '#type' => 'linkit',
    '#title' => $title,
    // Specify the autocomplete route for Linkit.
    '#autocomplete_route_name' => 'linkit.autocomplete',
    // Specify Linkit profile parameters.
    '#autocomplete_route_parameters' => [
      'linkit_profile_id' => 'default',
    ],
    '#default_value' => theme_get_setting($type_field_id),
  ];

  return $form;
}

/**
 * Helper function to build landing page option field set.
 *
 * @return array
 *   Form fieldset setup.
 */
function __jcc_landingpage_options_set($bundles, $section_id) {
  $excluded_content_types = [
    'alert',
    'arbitrator',
    'case',
    'custom_email',
    'document',
    'importer',
    'invitations_to_comment',
    'landing_page',
    'remote_hearings',
    'roc_rule',
    'roc_rule_index',
    'subpage',
    'tentative_ruling',
  ];

  $excluded_media_types = [
    'akamai_audio',
    'akamai_video',
    'boxcast_stream',
    'file',
    'image',
    'oembed_video',
    'remote_video',
    'snippet',
    'svg_file',
  ];

  // Create setting per content type.
  $landingpage_fieldset = [];
  foreach ($bundles as $key => $bundle) {
    if (!in_array($key, $excluded_content_types) && !in_array($key, $excluded_media_types)) {
      if ($key == 'judge') {
        $key = 'bio';
      }
      $type_field_id = 'landing_' . $key;
      if ($section_id) {
        $type_field_id = $type_field_id . '__' . $section_id;
      }

      // Support view display variants.
      switch ($key) {
        case 'opinion':
        case 'bio':
          // Option per view display variant.
          $excluded_displays = [
            'default',
          ];
          $view_names = [];
          if ($key == 'bio') {
            $view_names[] = 'justices_judges';
          }
          else {
            $view_names[] = 'opinions';
          }

          if ($view_names) {
            foreach ($view_names as $view_name) {
              $view = \Drupal::entityTypeManager()->getStorage('view')->load($view_name);
              if ($view) {
                $displays = $view->get('display');

                foreach ($displays as $display_name => $display) {
                  if (!in_array($display_name, $excluded_displays)) {
                    $type_field_id_variant = 'landing_' . $key . '_' . $display_name;
                    // Tag with section id if in use.
                    if ($section_id) {
                      $type_field_id_variant = 'landing_' . $key . '_' . $display_name . '__' . $section_id;
                    }

                    $landing_option_field = __jcc_landingpage_option_generator($bundle['label'] . ': ' . $display['display_title'], $type_field_id_variant);
                    $landingpage_fieldset[$type_field_id_variant] = $landing_option_field;
                  }
                }
              }
            }
          }
          break;

        default:
          $landingpage_fieldset[$type_field_id] = __jcc_landingpage_option_generator($bundle['label'], $type_field_id);
      }
    }
  }
  return $landingpage_fieldset;
}
