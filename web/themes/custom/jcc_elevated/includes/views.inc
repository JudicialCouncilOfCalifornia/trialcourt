<?php

/**
 * @file
 * Functions for views processing.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\views\ViewExecutable;
use Drupal\taxonomy\Entity\Term;

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function jcc_elevated_theme_suggestions_views_exposed_form_alter(array &$suggestions, array $variables) {
  if (strpos($variables['form']['#id'], 'views-exposed-form-') >= 0) {
    $form = str_replace('views-exposed-form-', '', $variables['form']['#id']);
    $form = str_replace('-', '_', $form);
    $suggestions[] = $variables['theme_hook_original'] . '__' . $form;
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function jcc_elevated_theme_suggestions_views_view_alter(array &$suggestions, array $variables) {
  // Add a suggestion based on the view name and the current display.
  $view = $variables['view'];
  $name = $view->id();
  $display = $view->current_display;
  $suggestions[] = 'views_view__' . $name;
  $suggestions[] = 'views_view__' . $name . '__' . $display;
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function jcc_elevated_theme_suggestions_views_view_unformatted_alter(array &$suggestions, array $variables) {
  // Add a suggestion based on the view name and the current display.
  $view = $variables['view'];
  $name = $view->id();
  $display = $view->current_display;
  $suggestions[] = 'views_view_unformatted__' . $name;
  $suggestions[] = 'views_view_unformatted__' . $name . '__' . $display;
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function jcc_elevated_theme_suggestions_views_view_fields_alter(array &$suggestions, array $variables) {
  // Add a suggestion based on the view name and the current display.
  $view = $variables['view'];
  $name = $view->id();
  $display = $view->current_display;
  $suggestions[] = 'views_view_fields__' . $name;
  $suggestions[] = 'views_view_fields__' . $name . '__' . $display;
}

/**
 * Implements hook_preprocess_HOOK() for news + sticky listing.
 */
function jcc_elevated_preprocess_views_view__news__sticky_list(&$variables) {
  if ($variables['view']->args && $variables['view']->args[0] != 'all') {
    $taxonomy_id = $variables['view']->args[0];
    $variables['more_custom_title'] = 'See All ' . Term::load($taxonomy_id)->get('name')->value . 's';
    $variables['more_custom_url'] = '/views/news?search=&type[' . $taxonomy_id . ']=' . $taxonomy_id . '&date_start=&date_end=';
  }
}

/**
 * Implements hook_preprocess_HOOK() for news listing.
 */
function jcc_elevated_preprocess_views_view__news__news_list(&$variables) {
  // Translation via TMGMT breaks dates where translators entered invalid date
  // values in date fields. if date is invalid clear it before template renders.
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $node = $row['#node'];
    $date = is_string($node->field_date->value) ? $node->field_date->value : '';
    if (!jcc_elevated_validate_date($date)) {
      $node->field_date->value = '';
    }
  }
  // Remove captcha.
  unset($variables['exposed']['captcha']);
}

/**
 * Implements hook_preprocess_HOOK() for publications listing.
 */
function jcc_elevated_preprocess_views_view__alerts__alerts(&$variables) {
  // Hide admin links.
  $variables['title_prefix'] = [];
  $variables['title_suffix'] = [];
}

/**
 * Implements hook_preprocess_HOOK() for publications listing.
 */
function jcc_elevated_preprocess_views_view__publications__publications_list(&$variables) {
  $render = \Drupal::service('renderer');
  $form = &$variables['exposed'];

  // Remove captcha.
  unset($form['captcha']);

  // Set the placeholder and Select all text.
  foreach (Element::children($form) as $id) {
    $form[$id]['#title_display'] = 'none';
    $placeholder = $form['#info']['filter-' . $id]['label'] ?? '';
    $form[$id]['#attributes']['placeholder'] = $placeholder;
    if ($form[$id]['#type'] == 'select') {
      $form[$id]['#attributes']['placeholder'] = NULL;
      if ($id != 'jcc_section') {
        $form[$id]['#options']['All'] = t('- @label -', ['@label' => $placeholder]);
      }
      $form['#info']['filter-' . $id]['label'] = '';
    }
  }

  // Add a label to the form.
  $form['search_label'] = [
    '#type' => 'html_tag',
    '#tag' => 'h2',
    '#value' => t('Search and Filter'),
    '#weight' => '-99',
  ];

  // Style the submit and reset buttons.
  $form['actions']['submit']['#attributes']['class'][] = 'button--secondary button--normal';
  if (isset($form['actions']['reset'])) {
    $form['actions']['reset']['#attributes']['class'][] = 'button--primary button--normal';
  }

  // Process the results for the search api view.
  $variables['processed_rows'] = [];
  foreach ($variables['rows'][0]['#rows'] as $item) {
    // Media object and ID.
    $media = $item['#row']->_entity;
    $label = $media->label();
    $description = $media->get('field_media_text')->getValue()[0]['value'] ?? '';
    $date = $media->get('field_date')->getValue()[0]['value'] ?? '';

    $topic = $media->get('field_topic')->referencedEntities() ? $media->get('field_topic')->referencedEntities()[0]->label() : NULL;
    $division = $media->get('field_division')->referencedEntities() ? $media->get('field_division')->referencedEntities()[0]->label() : NULL;
    $document_type = $media->get('field_document_type')->referencedEntities() ? $media->get('field_document_type')->referencedEntities()[0]->label() : NULL;
    $case_type = $media->get('field_case_type')->referencedEntities() ? $media->get('field_case_type')->referencedEntities()[0]->label() : NULL;

    $media_files = [
      'media_file' => [
        'file' => $media->get('field_media_file')->referencedEntities() ? $media->get('field_media_file')->referencedEntities()[0] : NULL,
        'language' => t('English'),
      ],
      'media_file_multiple' => [
        'file' => $media->get('field_media_file_multiple')->referencedEntities() ? $media->get('field_media_file_multiple')->referencedEntities()[0] : NULL,
        'language' => t('English Multiple'),
      ],
    ];

    $language_files = [
      'media_file_arabic' => [
        'file' => $media->get('field_media_file_arabic')->referencedEntities() ? $media->get('field_media_file_arabic')->referencedEntities()[0] : NULL,
        'language' => t('Arabic'),
      ],
      'media_file_cambodian' => [
        'file' => $media->get('field_media_file_cambodian')->referencedEntities() ? $media->get('field_media_file_cambodian')->referencedEntities()[0] : NULL,
        'language' => t('Cambodian'),
      ],
      'media_file_chinese_simple' => [
        'file' => $media->get('field_media_file_chinese_simple')->referencedEntities() ? $media->get('field_media_file_chinese_simple')->referencedEntities()[0] : NULL,
        'language' => t('Chinese - Simple'),
      ],
      'media_file_chinese' => [
        'file' => $media->get('field_media_file_chinese')->referencedEntities() ? $media->get('field_media_file_chinese')->referencedEntities()[0] : NULL,
        'language' => t('Chinese'),
      ],
      'media_east_armenian_file' => [
        'file' => $media->get('field_east_armenian_file')->referencedEntities() ? $media->get('field_east_armenian_file')->referencedEntities()[0] : NULL,
        'language' => t('East Armenian'),
      ],
      'media_file_farsi' => [
        'file' => $media->get('field_media_file_farsi')->referencedEntities() ? $media->get('field_media_file_farsi')->referencedEntities()[0] : NULL,
        'language' => t('Farsi'),
      ],
      'media_file_hmong' => [
        'file' => $media->get('field_media_file_hmong')->referencedEntities() ? $media->get('field_media_file_hmong')->referencedEntities()[0] : NULL,
        'language' => t('Hmong'),
      ],
      'media_file_korean' => [
        'file' => $media->get('field_media_file_korean')->referencedEntities() ? $media->get('field_media_file_korean')->referencedEntities()[0] : NULL,
        'language' => t('Korean'),
      ],
      'media_file_punjabi' => [
        'file' => $media->get('field_media_file_punjabi')->referencedEntities() ? $media->get('field_media_file_punjabi')->referencedEntities()[0] : NULL,
        'language' => t('Punjabi'),
      ],
      'media_file_russian' => [
        'file' => $media->get('field_media_file_russian')->referencedEntities() ? $media->get('field_media_file_russian')->referencedEntities()[0] : NULL,
        'language' => t('Russian'),
      ],
      'media_file_spanish' => [
        'file' => $media->get('field_media_file_spanish')->referencedEntities() ? $media->get('field_media_file_spanish')->referencedEntities()[0] : NULL,
        'language' => t('Spanish'),
      ],
      'media_file_tagalog' => [
        'file' => $media->get('field_media_file_tagalog')->referencedEntities() ? $media->get('field_media_file_tagalog')->referencedEntities()[0] : NULL,
        'language' => t('Tagalog'),
      ],
      'media_file_vietnamese' => [
        'file' => $media->get('field_media_file_vietnamese')->referencedEntities() ? $media->get('field_media_file_vietnamese')->referencedEntities()[0] : NULL,
        'language' => t('Vietnamese'),
      ],
    ];

    $files_for_pills = [];
    foreach ($language_files as $file) {
      if ($file['file']) {
        $url = Url::fromUri($file['file']->createFileUrl(FALSE));
        $url->setOptions(['attributes' => ['class' => ['pill__link']]]);
        $pill = [
          '#prefix' => '<div class="pill pill--default">',
          '#markup' => Link::fromTextAndUrl($file['language'], $url)->toString(),
          '#suffix' => '</div>',
        ];
        $files_for_pills[] = $render->render($pill);
      }
    }

    $text = ['#markup' => Html::decodeEntities($description)];
    $url = $media->toUrl();
    if ($media_files['media_file_multiple']['file']) {
      $url = Url::fromUri($media_files['media_file_multiple']['file']->createFileUrl(FALSE));
    }

    $button_data = [
      'label' => t('Download @type', ['@type' => $document_type]),
      'href' => $url,
    ];

    $detail_list_data = [
      'label' => t('Details'),
      'details' => array_filter([
        $topic,
        $date,
        $case_type,
        $division,
        $document_type,
      ]),
    ];

    $pills = [
      'items' => $files_for_pills,
      'icon_data' => [
        'icon' => 'translation',
        'color' => FALSE,
        'icon_path' => '/themes/contrib/jcc_storybook/src/assets/icons.svg',
      ],
    ];

    $variables['processed_rows'][] = [
      'title' => $label,
      'url' => $media->toUrl(),
      'main_file' => '',
      'content' => $render->render($text),
      'button_data' => $button_data,
      'detail_list_data' => $detail_list_data,
      'pills' => $files_for_pills ? $pills : [],
    ];
  }
}

/**
 * Implements hook_preprocess_views_view() for search listing page.
 */
function jcc_elevated_preprocess_views_view__search__search(&$variables) {
  $render = \Drupal::service('renderer');
  $view = $variables['view'];

  // Set the item count for setting in the title override.
  $variables['count'] = count($view->result);

  // Get the searched term for setting in the title override.
  $searched_term = $view->getExposedInput();
  $variables['searched_term'] = !empty($searched_term['search']) ? Xss::filter($searched_term['search']) : NULL;
  $variables['title'] = $view->getTitle();

  // Remove the captcha from the search.
  unset($variables['exposed']['captcha']);

  // Theme the search button.
  $variables['exposed']['actions']['submit']['#theme_wrappers'][0] = "input__submit__search_search_submit";

  // Process the results for the search api view.
  foreach ($variables['rows'][0]['#rows'] as $item) {
    // We have to manipulate the excerpt a little to render the html.
    $excerpt = $item['#row']->_item->getExcerpt() ?? t('No excerpt available.')->render();
    $text = [
      '#prefix' => '<p>',
      '#markup' => Html::decodeEntities($excerpt),
      '#suffix' => '</p>',
    ];

    $variables['processed_rows'][] = [
      'title' => $item['#row']->_entity->label(),
      'content' => $render->render($text),
      'url' => $item['#row']->_entity->toUrl()->toString(),
    ];
  }
}

/**
 * Implements hook_preprocess_HOOK() for justices and judges block view.
 */
function jcc_elevated_preprocess_views_view__justices_judges__current(&$variables) {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $persons = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $node = $row['#row']->_entity;
    $media = $node->get('field_media')->target_id ? Media::load($node->get('field_media')->target_id) : '';
    $name = $node->get('title')->value ? $node->get('title')->value : '';
    $title = $node->get('field_job_title')->target_id ? Term::load($node->get('field_job_title')->target_id)->get('name')->value : '';
    $division = $node->get('field_division')->target_id ? Term::load($node->get('field_division')->target_id)->get('name')->value : '';
    $start_date = $node->field_date->date ? $node->field_date->date->format('Y') : '';
    $url = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $node->id());
    $person = [
      'image' => $media,
      'name' => $name,
      'title' => $title,
      'division' => $division,
      'content' => 'Since ' . $start_date,
      'url' => $url,
    ];
    array_push($persons, $person);
  }

  // Group justices by division.
  $profiles = [];
  foreach ($persons as $person) {
    if ($person['division']) {
      $division = $person['division'];
      $profiles[$division][] = $person;
    }
    else {
      $profiles['Unassigned'][] = $person;
    }
  }

  $variables['profiles'] = $profiles;
}

/**
 * Implements hook_preprocess_HOOK() for justices and judges block view.
 */
function jcc_elevated_preprocess_views_view__justices_judges__former(&$variables) {
  // Reuse current justices/judges preprocess since no difference.
  jcc_elevated_preprocess_views_view__justices_judges__current($variables);
}

/**
 * Implements hook_views_pre_render().
 */
function jcc_elevated_views_pre_render(ViewExecutable $view) {
  if (($view->id() == 'search') && ($view->current_display == 'search')) {
    // @todo Hide some fields from display on the search page items.
    // $view->field['search_api_excerpt_1']->options['exclude'] = TRUE;.
    // $view->field['field_heading_1']->options['exclude'] = TRUE;.
  }
}

/**
 * Implements hook_views_pre_render().
 */
function jcc_elevated_views_post_render(ViewExecutable $view) {
  if (($view->id() == 'search') && ($view->current_display == 'search')) {
    // Set our default title for the search page.
    $view->setTitle(t('Search results'));
  }
}

/**
 * Prepare variables for views mini-pager templates.
 */
function jcc_elevated_preprocess_views_mini_pager(&$variables) {
  if (isset($variables['items']['current'])) {
    // Setting up the views mini_pager variables to better match system pager.
    $current = $variables['items']['current'];
    $variables['items']['pages'][$current] = [
      'title' => 'Current page',
      'href' => '',
    ];
  }

  if (isset($variables['items']['previous']['text'])) {
    // Remove previous text for the mini pager.
    $variables['items']['previous']['text'] = '';
  }

  if (isset($variables['items']['next']['text'])) {
    // Remove next text for the mini pager.
    $variables['items']['next']['text'] = '';
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function jcc_elevated_form_views_exposed_form_alter(array &$form, FormStateInterface $form_state, $form_id) {
  $view = $form_state->get('view');

  if (($view->id() == 'search') && ($view->current_display == 'search')) {
    // Remove captcha from search form.
    unset($form['captcha']);
    // Apply the form id to the items per page element, so it can be moved out.
    $form['items_per_page']['#attributes']['form'] = $form['#id'];
  }

  // Hide select form elements if their options are empty or only All/Any item.
  foreach (Element::children($form) as $name) {
    if (isset($form[$name]) && $form[$name]['#type'] == 'select') {

      // Disable Chosen on views select items. Re-enable on items as needed.
      $form[$name]['#attributes']['class'][] = 'chosen-disable';

      // Hide select items if empty options.
      if (empty($form[$name]['#options'])) {
        $form[$name]['#access'] = FALSE;
      }

      // Hide if only All/Any option is available.
      if (count($form[$name]['#options']) <= 1 && isset($form[$name]['#options']['All'])) {
        $form[$name]['#access'] = FALSE;
      }
    }
  }

}

/**
 * Helper function to validate a date string.
 *
 * @param string $date
 *   The string to validate.
 * @param string $format
 *   The optional date format to test.
 *
 * @return bool
 *   TRUE if the string is a valid date format, else FALSE.
 */
function jcc_elevated_validate_date(string $date, string $format = 'Y-m-d'): bool {
  $d = DateTime::createFromFormat($format, $date);
  return $d && $d->format($format) === $date;
}
