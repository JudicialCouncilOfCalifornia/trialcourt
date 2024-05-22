<?php

/**
 * @file
 * Functions for views processing.
 */

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\views\ViewExecutable;

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

  // Remove access to alerts that should not display on the page.
  $is_front = \Drupal::service('path.matcher')->isFrontPage();
  $current_page = \Drupal::routeMatch()->getParameter('node');
  foreach ($variables['rows'] as $index => $row) {
    $alerts = $variables['rows'][$index]['#rows'];
    foreach ($alerts as $delta => $alert) {
      $alert_node = $alert['#node'];
      if (!jcc_elevated_should_alert_display($alert_node, $current_page, $is_front)) {
        unset($variables['rows'][$index]['#rows'][$delta]);
      }
    }
  }

  $variables['#cache']['max-age'] = 0;
}

/**
 * Implements hook_preprocess_HOOK() for publication forms view.
 */
function jcc_elevated_preprocess_views_view__publication_forms(&$variables) {
  foreach ($variables['rows'] as $item) {
    $sub_items = [];
    foreach ($item['#rows'] as $sub_item) {
      $sub_items[] = $sub_item;
    }
    if (!empty($sub_items)) {
      $variables['accordion_items'][] = [
        'label' => $item['#title'],
        'content' => $sub_items,
      ];
    }
  }
}

/**
 * Implements hook_preprocess_HOOK() for publications listing.
 */
function jcc_elevated_preprocess_views_view__publications__publications_list(&$variables) {
  $render = \Drupal::service('renderer');

  // Customize filter UI.
  __jcc_view_results_exposed_filter_init($variables);

  // Language label sets.
  $lang_label_sets = __jcc_language_sets();

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
        'language' => is_array($lang_label_sets) ? $lang_label_sets['field_arabic_url']['label_default'] : '',
      ],
      'media_file_cambodian' => [
        'file' => $media->get('field_media_file_cambodian')->referencedEntities() ? $media->get('field_media_file_cambodian')->referencedEntities()[0] : NULL,
        'language' => is_array($lang_label_sets) ? $lang_label_sets['field_cambodian_url']['label_default'] : '',
      ],
      'media_file_chinese_simple' => [
        'file' => $media->get('field_media_file_chinese_simple')->referencedEntities() ? $media->get('field_media_file_chinese_simple')->referencedEntities()[0] : NULL,
        'language' => is_array($lang_label_sets) ? $lang_label_sets['field_chinese_url']['label_default'] : '',
      ],
      'media_file_chinese' => [
        'file' => $media->get('field_media_file_chinese')->referencedEntities() ? $media->get('field_media_file_chinese')->referencedEntities()[0] : NULL,
        'language' => is_array($lang_label_sets) ? $lang_label_sets['field_chinese_traditional_url']['label_default'] : '',
      ],
      'media_east_armenian_file' => [
        'file' => $media->get('field_east_armenian_file')->referencedEntities() ? $media->get('field_east_armenian_file')->referencedEntities()[0] : NULL,
        'language' => is_array($lang_label_sets) ? $lang_label_sets['field_east_armenian_url']['label_default'] : '',
      ],
      'media_file_farsi' => [
        'file' => $media->get('field_media_file_farsi')->referencedEntities() ? $media->get('field_media_file_farsi')->referencedEntities()[0] : NULL,
        'language' => is_array($lang_label_sets) ? $lang_label_sets['field_farsi_url']['label_default'] : '',
      ],
      'media_file_hmong' => [
        'file' => $media->get('field_media_file_hmong')->referencedEntities() ? $media->get('field_media_file_hmong')->referencedEntities()[0] : NULL,
        'language' => is_array($lang_label_sets) ? $lang_label_sets['field_hmong_url']['label_default'] : '',
      ],
      'media_file_korean' => [
        'file' => $media->get('field_media_file_korean')->referencedEntities() ? $media->get('field_media_file_korean')->referencedEntities()[0] : NULL,
        'language' => is_array($lang_label_sets) ? $lang_label_sets['field_korean_url']['label_default'] : '',
      ],
      'media_file_punjabi' => [
        'file' => $media->get('field_media_file_punjabi')->referencedEntities() ? $media->get('field_media_file_punjabi')->referencedEntities()[0] : NULL,
        'language' => is_array($lang_label_sets) ? $lang_label_sets['field_punjabi_url']['label_default'] : '',
      ],
      'media_file_russian' => [
        'file' => $media->get('field_media_file_russian')->referencedEntities() ? $media->get('field_media_file_russian')->referencedEntities()[0] : NULL,
        'language' => is_array($lang_label_sets) ? $lang_label_sets['field_russian_url']['label_default'] : '',
      ],
      'media_file_spanish' => [
        'file' => $media->get('field_media_file_spanish')->referencedEntities() ? $media->get('field_media_file_spanish')->referencedEntities()[0] : NULL,
        'language' => is_array($lang_label_sets) ? $lang_label_sets['field_spanish_url']['label_default'] : '',
      ],
      'media_file_tagalog' => [
        'file' => $media->get('field_media_file_tagalog')->referencedEntities() ? $media->get('field_media_file_tagalog')->referencedEntities()[0] : NULL,
        'language' => is_array($lang_label_sets) ? $lang_label_sets['field_tagalog_url']['label_default'] : '',
      ],
      'media_file_vietnamese' => [
        'file' => $media->get('field_media_file_vietnamese')->referencedEntities() ? $media->get('field_media_file_vietnamese')->referencedEntities()[0] : NULL,
        'language' => is_array($lang_label_sets) ? $lang_label_sets['field_vietnamese_url']['label_default'] : '',
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

  // Get the searched term for setting in the title override.
  $searched_term = $view->getExposedInput();
  $variables['searched_term'] = !empty($searched_term['search']) ? Xss::filter($searched_term['search']) : NULL;
  $variables['title'] = $view->getTitle();

  // Personalized no results message.
  $variables['no_results_heading'] = 'We are unable to find a result for your search.';
  if (theme_get_setting('no_search_results_heading')) {
    $variables['no_results_heading'] = theme_get_setting('no_search_results_heading');
  }
  $variables['no_results_message'] = ['#markup' => Html::decodeEntities('Visit our <a href="/">homepage</a> for more options.')];
  $no_results_msg = theme_get_setting('no_search_results_message');
  if ($no_results_msg && $no_results_msg['value']) {
    $variables['no_results_message'] = ['#markup' => Html::decodeEntities($no_results_msg['value'])];
  }

  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  // Remove the captcha from the search.
  unset($variables['exposed']['captcha']);

  // Theme the search button.
  $variables['exposed']['actions']['submit']['#theme_wrappers'][0] = "input__submit__search_search_submit";

  // Process the results for the search api view.
  foreach ($variables['rows'][0]['#rows'] as $index => $item) {
    // We have to manipulate the excerpt a little to render the html.
    $excerpt = $item['#row']->_item->getExcerpt() ?? t('No excerpt available.')->render();
    $text = [
      '#prefix' => '<p>',
      '#markup' => Html::decodeEntities($excerpt),
      '#suffix' => '</p>',
    ];

    $entity = $item['#row']->_entity;

    $variables['processed_rows'][$index] = [
      'title' => $entity->label(),
      'content' => $render->render($text),
      'url' => $entity->toUrl()->toString(),
    ];

    // Append section as a notation to search results, if module enabled and
    // section exists.
    $module_handler = \Drupal::service('module_handler');
    if ($module_handler->moduleExists('jcc_elevated_sections')) {
      $section_service = \Drupal::service('jcc_elevated_sections.service');
      $section = $section_service->getSectionForNode($entity);
      if ($section_info = $section_service->getSectionInfo($section)) {
        if ($section_name = $section_info->getName()) {
          $variables['processed_rows'][$index]['brow']['notation'] = $section_name;
        }
      }
    }
  }

  // Count summary for the current page of results.
  $variables['count_summary'] = $variables['header']['result']['#markup'] ?? '';
}

/**
 * Implements hook_preprocess_HOOK() for current justices and judges block view.
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
    $title = '';
    if ($title_tid = $node->get('field_job_title')->target_id) {
      $title = Term::load($title_tid) ? Term::load($title_tid)->get('name')->value : '';
    }
    $division = '';
    if ($division_tid = $node->get('field_division')->target_id) {
      $division = Term::load($division_tid) ? Term::load($division_tid)->get('name')->value : '';
    }
    $url = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $node->id());
    // Service time.
    $start_date = $node->field_date->date ? $node->field_date->date->format('Y') : '';
    $retirement_date = $node->field_retirement_date->date ? $node->field_retirement_date->date->format('Y') : '';
    $service_time = 'Since ' . $start_date;
    if ($retirement_date) {
      $service_time = 'From ' . $start_date . ' to ' . $retirement_date;
    }

    $person = [
      'image' => $media,
      'name' => $name,
      'title' => $title,
      'division' => $division,
      'content' => $service_time,
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
  // Reuse current justices/judges as common preprocess.
  jcc_elevated_preprocess_views_view__justices_judges__current($variables);
}

/**
 * Helper function to get node titles by term ID.
 */
function get_node_titles_by_term($term_id) {
  $query = \Drupal::entityQuery('node')
    ->condition('status', 1)
    ->condition('type', 'facilities')
    ->condition('field_award', $term_id);
  $nids = $query->execute();
  $nodes = Node::loadMultiple($nids);
  $titles = [];
  foreach ($nodes as $node) {
    $titles[] = $node->getTitle();
  }
  return $titles;
}

/**
 * Implements hook_preprocess_views_view() for awards recognition block.
 */
function jcc_elevated_preprocess_views_view__facilities_awards_and_recognition__block_1(&$variables) {
  __jcc_view_results_exposed_filter_init($variables);
  $parent_children_map = [];
  $terms_noparent = [];
  $all_parent_ids = [];
  foreach ($variables['view']->result as $result) {
    if (isset($result->_entity) && $result->_entity instanceof Term) {
      $term = $result->_entity;
      $parent_tid = $term->get('parent')->target_id;
      if ($parent_tid) {
        $parent_children_map[$parent_tid][] = $term;
        $all_parent_ids[] = $parent_tid;
      }
      else {
        $terms_noparent[] = $term;
      }
    }
  }
  $items = [];

  foreach ($parent_children_map as $parent_tid => $children) {
    $parent_term_entity = Term::load($parent_tid);
    if ($parent_term_entity) {
      $parent_name = $parent_term_entity->getName();
      $children_details = '';
      foreach ($children as $child) {
        $child_name = $child->getName();
        $children_details .= "<p>{$child_name} ";
        $node_titles = get_node_titles_by_term($child->id());
        if (!empty($node_titles)) {
          $children_details .= ": " . implode(", ", $node_titles);
        }
        $children_details .= "</p>";
      }

      $items[] = [
        'label' => $parent_name,
        'content' => [
          '#markup' => Markup::create($children_details),
        ],
        'id' => $parent_tid,
      ];
    }
  }

  $misc_awards = '';
  foreach ($terms_noparent as $child) {
    if (!in_array($child->id(), $all_parent_ids)) {
      $child_name = $child->getName();
      $misc_awards .= "<p>{$child_name} ";
      if (!empty($node_titles)) {
        $misc_awards .= ": " . implode(", ", $node_titles);
      }
      $misc_awards .= "</p>";
    }
  }

  if (!empty($misc_awards)) {
    $items[] = [
      'label' => t('Miscellaneous'),
      'content' => [
        '#markup' => Markup::create($misc_awards),
      ],
      'id' => 'awards',
    ];
  }
  $variables['items'] = $items;
}

/**
 * Implements hook_preprocess_views_view() for facilities block.
 */
function jcc_elevated_preprocess_views_view__facilities_listing__block_1(&$variables) {
  __jcc_view_results_exposed_filter_init($variables);
  $entity_type_manager = \Drupal::service('entity_type.manager');
  $taxonomy_manager = $entity_type_manager->getStorage('taxonomy_term');
  $facilities = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $node = $row['#node'];
    $detail_list_data = [
      'details' => array_filter([
        $node->get('field_county')->value,
      ]),
    ];
    $status = $node->get('field_project_status')->value == 'inprogress' ? 'In-Progress' : 'Completed';
    $county = $node->hasField('field_facility_county') ? $node->get('field_facility_county')->target_id : FALSE;
    $county_value = $county && $taxonomy_manager->load($county) ? $taxonomy_manager->load($county)->get('name')->value : '';
    $facility = [
      'title' => $node->get('title')->value ? $node->get('title')->value : '',
      'status' => $status ? ['#markup' => Html::decodeEntities('<strong>Status:</strong> ' . $status)] : '',
      'county' => $county_value ? $county_value : '',
      'url' => $node->id() ? \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $node->id()) : '',
      'detail_list_data' => $detail_list_data,
    ];
    $facilities[] = $facility;
  }
  $variables['facilities'] = $facilities;
}

/**
 * Implements hook_preprocess_views_view() for job search listing block.
 */
function jcc_elevated_preprocess_views_view__jobs__search(&$variables) {
  $render = \Drupal::service('renderer');
  $entity_type_manager = \Drupal::service('entity_type.manager');
  $taxonomy_manager = $entity_type_manager->getStorage('taxonomy_term');

  // Customize filter UI.
  __jcc_view_results_exposed_filter_init($variables);

  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $jobs = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $node = $row['#node'];

    // Render deadline date.
    $deadline = $node->get('unpublish_on')->value ? date('F j, Y', $node->get('unpublish_on')->value) : '';
    // If no deadline or 12/12/9999.
    if (!$deadline || $deadline === 'December 12, 9999') {
      $deadline = t('Open Until Filled');
    }

    // Get teaser.
    $teaser = $node->get('body') ?? '';
    if ($teaser) {
      $teaser = $node->get('body')->view('teaser');
      $teaser = strval($render->render($teaser));
      $teaser = strip_tags($teaser);
    }

    // Get locations.
    $locations = __jcc_get_job_locations($node);

    // Assemble details.
    $detail_list_data = [
      'details' => array_filter([
        $locations ? $locations . ', CA' : '',
        $node->get('field_is_temporary')->value ? 'Temporary' : 'Regular' . ' Employment',
      ]),
    ];

    // Check for a job type id first. This is just here because with dev work
    // It is possible to have a job node without the field or type declared.
    $job_type_id = $node->hasField('field_job_type') ? $node->get('field_job_type')->target_id : FALSE;

    $job = [
      'id' => $node->get('field_job_id')->value ? 'ID: ' . $node->get('field_job_id')->value : '',
      'deadline' => $deadline ? ['#markup' => Html::decodeEntities('<strong>Deadline:</strong> ' . $deadline)] : '',
      'type' => is_numeric($job_type_id) && !empty($taxonomy_manager->load($job_type_id)) ? $taxonomy_manager->load($job_type_id)->get('name')->value : '',
      'title' => [
        'text' => $node->get('title')->value ?? '',
        'url' => $node->id() ? \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $node->id()) : '',
      ],
      'teaser' => $teaser,
      'detail_list_data' => $detail_list_data,
    ];
    $jobs[] = $job;
  }

  // Count summary for the current page of results.
  $jobs['count_summary'] = $variables['header']['result']['#markup'] ?? '';

  $variables['jobs'] = $jobs;
}

/**
 * Implements hook_preprocess_views_view() for job search listing page.
 */
function jcc_elevated_preprocess_views_view__jobs__search_page(&$variables) {
  // Reuse current jobs search block as common preprocess.
  jcc_elevated_preprocess_views_view__jobs__search($variables);
}

/**
 * Implements hook_preprocess_HOOK() for Requests(RPF) listing.
 */
function jcc_elevated_preprocess_views_view__requests__requests_rfp_listing(&$variables): void {
  // Customize filter UI.
  __jcc_view_results_exposed_filter_init($variables);

  // Process the results for the search api view.
  $variables['processed_rows'] = [];
  if (isset($variables['rows'][0]['#rows'])) {
    foreach ($variables['rows'][0]['#rows'] as $item) {
      $request = $item['#row']->_entity;

      $brow = [];
      $items = [
        'primary' => $request->get('field_rfp_type')
          ->first() ? $request->get('field_rfp_type')
          ->first()
          ->view('teaser') : '',
        'secondary' => $request->get('field_rfp_number')
          ->first() ? $request->get('field_rfp_number')
          ->first()
          ->view('teaser') : '',
        'notation' => $request->get('field_rfp_start_date')
          ->first() ? $request->get('field_rfp_start_date')
          ->first()
          ->view('teaser') : '',
      ];
      foreach ($items as $key => $value) {
        if (!empty($value)) {
          if ($key == 'secondary') {
            $value['#context']['value'] = t('No.') . ' ' . $value['#context']['value'];
          }
          if ($key == 'notation') {
            $value['#text'] = t('Issued:') . ' ' . $value['#text'];
          }
          $brow[$key] = $value;
        }
      }

      $details = [];
      $items = [
        'status' => $request->get('field_rfp_status')
          ->first() ? $request->get('field_rfp_status')
          ->first()
          ->view('teaser') : '',
        'deadline' => $request->get('field_rfp_end_date')
          ->first() ? $request->get('field_rfp_end_date')
          ->first()
          ->view('teaser') : '',
        'last_updated' => t('Last updated:') . ' ' . \Drupal::service('date.formatter')
          ->format($request->getChangedTime(), 'm_d_y'),
      ];
      foreach ($items as $key => $value) {
        if (!empty($value)) {
          if ($key == 'status') {
            $value['#markup'] = t('Status:') . ' ' . $value['#markup']->__toString();
          }
          if ($key == 'deadline') {
            $value['#text'] = t('Deadline:') . ' ' . $value['#text'];
          }
          $details[$key] = $value;
        }
      }

      $variables['processed_rows'][] = [
        'brow' => $brow,
        'title' => $request->label(),
        'url' => $request->toUrl(),
        'content' => $request->get('body')->first() ? $request->get('body')
          ->first()
          ->view('teaser') : '',
        'detail_list_data' => [
          'label' => t('Details'),
          'details' => $details,
        ],
        'item_classes' => ['result-extra-style__heading__lg'],
      ];
    }
  }
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

  if (($view->id() == 'publications') && ($view->current_display == 'publications_list')) {

    // Specifically hide the Case type filter from the publication list on LAS.
    if (function_exists('jcc_elevated_get_site_name') && jcc_elevated_get_site_name() == 'partners') {
      $form['field_case_type_target_id']['#access'] = FALSE;
    }
    // Removing date filter for elevated themes.
    unset($form['field_date_value_wrapper']);
  }

  // Hide select form elements if their options are empty or only All/Any item.
  foreach (Element::children($form) as $name) {
    if (isset($form[$name]) && isset($form[$name]['#type']) && $form[$name]['#type'] == 'select') {

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

/**
 * Helper function to apply exposed filter UI customizations.
 */
function __jcc_view_results_exposed_filter_init(&$variables) {
  $form = &$variables['exposed'];

  // Remove captcha.
  unset($form['captcha']);

  // Set the placeholder and Select all text.
  foreach (Element::children($form) as $id) {
    $placeholder = $form['#info']['filter-' . $id]['label'] ?? '';
    $form[$id]['#attributes']['placeholder'] = $placeholder;

    if (isset($form[$id]['#type']) && $form[$id]['#type'] == 'select') {
      // Set label as 'select all' option label.
      $form[$id]['#attributes']['placeholder'] = NULL;
      if ($id != 'jcc_section') {
        $form[$id]['#options']['All'] = t('- @label -', ['@label' => $placeholder]);
      }
      // Visually hide field label for accessibility.
      // form-element-label.html.twig.
      $form[$id]['#title_display'] = 'invisible';
    }
  }

  // Add a label to the form.
  $form['search_label'] = [
    '#type' => 'html_tag',
    '#tag' => 'h2',
    '#value' => t('Search and Filter'),
    '#weight' => '-99',
  ];

  // Style submit and reset buttons.
  $form['actions']['submit']['#attributes']['class'][] = 'button button--secondary button--normal';
  if (isset($form['actions']['reset'])) {
    $form['actions']['reset']['#attributes']['class'][] = 'button button--secondary button--normal';
  }
}
