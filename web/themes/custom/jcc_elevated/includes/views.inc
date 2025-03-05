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
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\views\ResultRow;
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
 * Implements hook_preprocess_HOOK() for news listing block.
 */
function jcc_elevated_preprocess_views_view__news__list_block(&$variables) {
  // Customize filter UI.
  __jcc_view_results_exposed_filter_init($variables);

  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  // Translation via TMGMT breaks dates where translators entered invalid date
  // values in date fields. if date is invalid clear it before template renders.
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $node = $row['#node'];
    $date = is_string($node->field_date->value) ? $node->field_date->value : '';
    if (!jcc_elevated_validate_date($date)) {
      $node->field_date->value = '';
    }
  }
}

/**
 * Implements hook_preprocess_HOOK() for news listing.
 */
function jcc_elevated_preprocess_views_view__news__news_list(&$variables) {
  // Reuse news block list as common preprocess.
  jcc_elevated_preprocess_views_view__news__list_block($variables);
}

/**
 * Implements hook_preprocess_HOOK() for alerts.
 */
function jcc_elevated_preprocess_views_view__alerts__alerts(&$variables) {
  $variables['attributes']['class'][] = 'stack global-alerts';
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
    $excerpt = $item['#row']->_item->getExcerpt() ?? t('No preview is available for this search result. Click for more information.')->render();
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

    // Add metadata details based on content type.
    switch ($entity->bundle()) {

      case 'advisory_body':
        $variables['processed_rows'][$index]['brow'] = [
          'primary' => 'Advisory Body',
        ];
        break;

      case 'event':
        $variables['processed_rows'][$index]['brow'] = [
          'primary' => 'Event',
          'secondary' => \Drupal::service('date.formatter')
            ->format(strtotime($entity?->field_event_date?->value), 'm_d_y'),
        ];
        break;

      case 'judge':
        $variables['processed_rows'][$index]['brow'] = [
          'primary' => 'Profile',
          'secondary' => $entity?->field_person_type?->entity?->label(),
        ];
        break;

      case 'landing_page':
      case 'subpage':
        $routeName = $entity->toUrl()->getRouteName();
        $routeParameters = $entity->toUrl()->getRouteParameters();
        $route = \Drupal::service('router.route_provider')->getRouteByName($routeName);
        $routeMatch = new RouteMatch($routeName, $route, $routeParameters, $routeParameters);
        $breadcrumb = \Drupal::service('breadcrumb')->build($routeMatch);
        $breadcrumb_links = array_slice($breadcrumb->getLinks(), 0, -1);

        if ($breadcrumb_links) {
          $link_titles = [];
          foreach ($breadcrumb_links as $link) {
            $link_titles[] = ucwords($link->getText());
          }
          $variables['processed_rows'][$index]['detail_list_data'] = [
            'details' => $link_titles,
          ];
        }

        break;

      case 'news':
        $variables['processed_rows'][$index]['brow'] = [
          'primary' => 'News',
          'secondary' => \Drupal::service('date.formatter')
            ->format(strtotime($entity?->field_date?->value), 'm_d_y'),
        ];
        break;

      case 'opinion':
        $variables['processed_rows'][$index]['brow'] = [
          'primary' => 'Opinion',
          'secondary' => $entity?->field_case_number_plain?->value,
        ];
        break;

      case 'request':
        $variables['processed_rows'][$index]['brow'] = [
          'primary' => 'Solicitation',
          'secondary' => strtoupper($entity?->field_rfp_type?->value ?? ''),
        ];
        break;

      case 'roc_rule':
      case 'roc_rule_index':
        $variables['processed_rows'][$index]['brow'] = [
          'primary' => 'Rules of Court',
        ];
        break;

      default:
        $variables['processed_rows'][$index]['brow'] = [
          'primary' => $entity->type->entity->label(),
        ];
        break;
    }

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
 * Implements hook_preprocess_views_view() for events listing block.
 */
function jcc_elevated_preprocess_views_view__advisory_body_events__block_1(&$variables) {
  $render = \Drupal::service('renderer');
  $entity_type_manager = \Drupal::service('entity_type.manager');
  $taxonomy_manager = $entity_type_manager->getStorage('taxonomy_term');

  // Check if user can add events.
  $_url = Url::fromRoute('node.add', ['node_type' => 'event']);
  $userCurrent = \Drupal::currentUser();

  if ($_url->access($userCurrent)) {
    $variables['header']['#markup'] = '<div><a class="button button--secondary button--normal icon--arrow  " href="/node/add/event">Add event</a></div><div></div>';
  }

  if (count($variables['rows']) == 0) {
    $variables['no_results_msg']['#markup'] = $variables['empty']['area']['#text'];
  }
  foreach ($variables['rows'] as $group) {
    foreach ($group['#rows'] as $row) {
      $node = $row['#node'];
      $event = [];

      // Get full body content.
      $teaser = $node->get('body') ?? '';
      if ($teaser) {
        $teaser = $node->get('body')->view('advisory_body_events');
        $teaser = strval($render->render($teaser));
      }

      // Get files.
      $event_files = '';
      if ($node->hasField('field_event_files')) {
        foreach ($node->get('field_event_files') as $file) {
          $media = Media::load($file->target_id);
          $fid = $media->field_media_file->target_id;
          $file = File::load($fid);
          if ($file) {
            $url = $file->createFileUrl();
            $event_files .= '<a class="file-download" href="' . $url . '">' . $media->field_media_file->description . '</a> ';
          }
        }
      }

      // Get Event state.
      $event_state_id = $node->hasField('field_event_state') ? $node->get('field_event_state')->target_id : FALSE;

      // Get Event granicus link.
      $event_granicus_link = $node->hasField('field_granicus_link') && $node->get('field_granicus_link')->first() ? $node->get('field_granicus_link')->view()[0]["#text"] : FALSE;

      // Get event time start and end.
      $temp_start_date = $node->field_event_date->start_date->format('Y-m-d H:i:s');
      $temp_end_date = $node->field_event_date->end_date->format('Y-m-d H:i:s');

      // Having to hardcode the timezone into the date.
      $s_date = new DateTime($temp_start_date, new DateTimeZone('UTC'));
      $s_date->setTimeZone(new DateTimeZone("America/Los_Angeles"));
      $start_date = $s_date->format('g:i a');

      $e_date = new DateTime($temp_end_date, new DateTimeZone('UTC'));
      $e_date->setTimeZone(new DateTimeZone("America/Los_Angeles"));
      $end_date = $e_date->format('g:i a');

      $event_date_time = '<strong>Time: </strong> ' . $start_date . ' to ' . $end_date;

      // Check if user has edit permissions for events.
      $node = Node::load($node->id());
      $check_edit_access = $node->access('update', $userCurrent);
      $event['content']['#markup'] = '';

      if ($check_edit_access) {
        $event['content']['#markup'] .= '<a href="/node/' . $node->id() . '/edit">(edit this event)<br/></a>';
      }
      $event['content']['#markup'] = $event['content']['#markup'] ?? '';

      // Populating the event object.
      $event['title'] = $s_date->format('F j, Y');
      if ($teaser != '') {
        $event['content']['#markup'] .= '<div>' . $teaser . '</div>';
      }
      $event['content']['#markup'] .= is_numeric($event_state_id) && !empty($taxonomy_manager->load($event_state_id)) ? '<strong>Event Type: </strong>' . $taxonomy_manager->load($event_state_id)->get('name')->value : '';
      $event['content']['#markup'] .= '<div>' . $event_date_time . '</div>';

      if ($event_granicus_link != '') {
        $event['content']['#markup'] .= '<div><strong>' . $node->get('field_granicus_link')->view()['#title'] . ': </strong>' . $event_granicus_link . '</div>';
      }

      if ($event_files != '') {
        $event['content']['#markup'] .= '<div>' . $event_files . '</div>';
      }

      $variables['events'][] = $event;
    }
  }
}

/**
 * Implements hook_preprocess_views_view() for events listing block.
 */
function jcc_elevated_preprocess_views_view__advisory_body_events__page_1(&$variables) {
  $render = \Drupal::service('renderer');
  $entity_type_manager = \Drupal::service('entity_type.manager');
  $taxonomy_manager = $entity_type_manager->getStorage('taxonomy_term');
  $node_manager = $entity_type_manager->getStorage('node');
  $media_manager = $entity_type_manager->getStorage('media');
  $file_manager = $entity_type_manager->getStorage('file');

  // Customize filter UI.
  __jcc_view_results_exposed_filter_init($variables);

  // Get our URL argument from the view args.
  $advisory_body_id = $variables['view']->args[0];
  $parent_advisory_body = $node_manager->load($advisory_body_id);

  // Check if we are dealing with an advisory body.
  if (!$parent_advisory_body instanceof NodeInterface || !$parent_advisory_body->getType() == 'advisory_body') {
    $parent_advisory_body = FALSE;
  }

  foreach ($variables['rows'] as $key => $group) {
    // Process date range year for grouping label.
    // Remove markup and line breaks.
    $list_label = preg_replace('/\R+/', ' ', strip_tags($group['#title']));
    // If date range detected, set end date year as label.
    if (($position = strpos($list_label, '-')) !== FALSE) {
      $list_label = substr($list_label, $position + 1);
    }
    $variables['events'][$key]['list_label'] = $list_label;
    // Past events for the year.
    foreach ($group['#rows'] as $row) {
      $node = $row['#node'];
      $event = [];

      // Get full body content.
      $teaser = $node->get('body') ?? '';
      if ($teaser) {
        $teaser = $node->get('body')->view('advisory_body_events');
        $teaser = strval($render->render($teaser));
      }

      // Get files.
      $event_files = '';
      if ($node->hasField('field_event_files')) {
        foreach ($node->get('field_event_files') as $file) {
          $media = $media_manager->load($file->target_id);
          $fid = $media->field_media_file->target_id;
          $file = $fid ? $file_manager->load($fid) : FALSE;
          if ($file) {
            $url = $file->createFileUrl();
            $event_files .= '<a class="file-download" href="' . $url . '">' . $media->field_media_file->description . '</a> ';
          }
        }
      }

      // Get Event granicus link.
      $event_granicus_link = $node->hasField('field_granicus_link') && $node->get('field_granicus_link')->first() ? $node->get('field_granicus_link')->view()[0]["#text"] : FALSE;

      // Get Event state.
      $event_state_id = $node->hasField('field_event_state') ? $node->get('field_event_state')->target_id : FALSE;

      // Check if user has edit permissions for events.
      $node = Node::load($node->id());
      $user = User::load(\Drupal::currentUser()->id());
      $check_edit_access = $node->access('update', $user);

      $event['content']['#markup'] = '';

      if ($check_edit_access) {
        $event['content']['#markup'] .= '<a href="/node/' . $node->id() . '/edit">(edit this event)<br/></a>';
      }

      $event['title'] = $node->get('title')->value;
      $event['content']['#markup'] .= is_numeric($event_state_id) && !empty($taxonomy_manager->load($event_state_id)) ? '<strong>Event Type: </strong>' . $taxonomy_manager->load($event_state_id)->get('name')->value : '';
      if ($teaser != '') {
        $event['content']['#markup'] .= '<div>' . $teaser . '</div>';
      }
      if ($event_granicus_link != '') {
        $event['content']['#markup'] .= '<div><strong>' . $node->get('field_granicus_link')->view()['#title'] . ': </strong>' . $event_granicus_link . '</div>';
      }
      if ($event_files != '') {
        $event['content']['#markup'] .= '<div>' . $event_files . '</div>';
      }

      $variables['events'][$key]['items'][] = $event;
    }
  }

  $variables['exposed'] = $parent_advisory_body ? $variables['exposed'] : [];

  // If we have an advisory body in the URL context, adjust the title.
  $variables['view_title'] = $parent_advisory_body ? t('@title Past Meetings', ['@title' => $parent_advisory_body->getTitle()])->__toString() : t('Past Meetings')->__toString();
  $variables['view_brow'] = $parent_advisory_body ? t('Advisory Committee') : NULL;
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
      $node_titles = get_node_titles_by_term($child->id());
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
        $node->get('field_facility_county')->value,
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
  usort($facilities, fn($a, $b) => $a['county'] <=> $b['county']);
  $variables['facilities'] = $facilities;
}

/**
 * Generates the webform URL for ITC.
 */
function generate_itc_webform_url($item_number, $formatted_deadline, $title) {
  $base_path = '/itc-webform';
  $webform_subpage_url = Url::fromUserInput($base_path, [
    'query' => [
      'proposal' => htmlspecialchars($item_number, ENT_QUOTES, 'UTF-8'),
      'deadline' => htmlspecialchars($formatted_deadline, ENT_QUOTES, 'UTF-8'),
      'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
    ],
  ])->setAbsolute()->toString();
  return $webform_subpage_url;
}

/**
 * Implements hook_preprocess_views_view() for ITC past proposals block.
 */
function jcc_elevated_preprocess_views_view__past_proposals__block_1(&$variables) {
  __jcc_view_results_exposed_filter_init($variables);
  $entity_type_manager = \Drupal::service('entity_type.manager');
  $taxonomy_manager = $entity_type_manager->getStorage('taxonomy_term');
  $grouped_proposals = [];
  $file_url = '';
  $file_uri = '';
  foreach ($variables['rows'] as $row1) {
    $list_label = preg_replace('/\R+/', ' ', strip_tags($row1['#title']));
    $safe_list_label = rawurlencode($list_label);
    if (!isset($grouped_proposals[$safe_list_label])) {
      $grouped_proposals[$safe_list_label] = [
        'list_label' => '',
        'items' => [],
      ];
    }
    foreach ($row1['#rows'] as $row) {
      $result_row = $row['#row'];
      if (!empty($result_row->_entity) && $result_row->_entity instanceof Node) {
        $node = $result_row->_entity;
        $media_entities = $node->get('field_file')->referencedEntities();
        if (!empty($media_entities)) {
          $media_entity = $media_entities[0];
          if ($media_entity) {
            $file_ids = $media_entity->get('field_media_file')->getValue();
            if (!empty($file_ids)) {
              $file_id = $file_ids[0]['target_id'];
              $file = File::load($file_id);
              if ($file) {
                $file_uri = $file->getFileUri();
              }
              if (!empty($file_uri)) {
                $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file_uri);
              }
            }
          }
        }
        $brow = [];
        if ($node->hasField('field_deadline') && !$node->field_deadline->isEmpty()) {
          $deadline = $node->field_deadline->value;
          $utc_deadline = new DateTime($deadline, new DateTimeZone('UTC'));
          $desired_timezone = new DateTimeZone('America/Los_Angeles');
          $utc_deadline->setTimezone($desired_timezone);
          $current_time = new DateTime('now', $desired_timezone);
          $formatted_deadline = $utc_deadline->format('F j, Y');
          $item_number = $node->get('field_itc_number')->value;
          $title = $node->get('title')->value;
          $brow['secondary'] = $item_number;
          if ($utc_deadline > $current_time) {
            $webform_url = generate_itc_webform_url($item_number, $formatted_deadline, $title);
            $email_address = 'invitations@jud.ca.gov';
            $url_content = '<a href="' . $webform_url . '">Submit comments online</a> | <a href="mailto:' . $email_address . '">Comment by email</a></p>';
            $body = $node->get('body')->value;
            $body .= '<p>' . $url_content . '</p>';
          }
          else {
            $body = $node->get('body')->value;
          }
        }
        else {
          $body = $node->get('body')->value;
        }
        $item_number = $node->get('field_itc_number')->value;
        $brow['secondary'] = $item_number;
        $category = $node->hasField('field_category') ? $node->get('field_category')->target_id : FALSE;
        $category_value = $category && $taxonomy_manager->load($category) ? $taxonomy_manager->load($category)->get('name')->value : '';
        $brow['primary'] = $category_value;
        $deadline_value = $node->field_deadline->value;
        if (!empty($deadline_value)) {
          $utc_deadline = new DateTime($deadline_value, new DateTimeZone('UTC'));
          $desired_timezone = new DateTimeZone('America/Los_Angeles');
          $utc_deadline->setTimezone($desired_timezone);
          $formatted_deadline = $utc_deadline->format('F j, Y');
          $formatted_value = t('Deadline:') . ' ' . $formatted_deadline;
          $brow['notation'] = $formatted_value;
          $raw_deadline = $utc_deadline->getTimestamp();
        }
        else {
          $deadline = '';
          $brow['notation'] = '';
          $raw_deadline = 0;
        }

        $itc_past_proposal = [
          'brow' => [
            'primary' => $brow['primary'] ?? '',
            'secondary' => $brow['secondary'] ?? '',
            'notation' => $brow['notation'] ?? '',
          ],
          'title' => $node->get('title')->value ? $node->get('title')->value : '',
          'category' => $category_value ? $category_value : '',
          'content' => [
            '#markup' => $body,
          ],
          'item_number' => $node->get('field_itc_number')->value,
          'file_url' => $file_url ? $file_url : '',
          'raw_deadline' => $raw_deadline,
        ];
        if (!empty($file_url)) {
          $itc_past_proposal['button_data'] = [
            'label' => t('Download PDF'),
            'href' => $file_url,
          ];
        }
        $grouped_proposals[$safe_list_label]['items'][] = $itc_past_proposal;
      }
    }
    if (count($grouped_proposals[$safe_list_label]['items']) >= 1) {
      $grouped_proposals[$safe_list_label]['list_label'] = $list_label;
    }
  }
  usort($grouped_proposals, function ($a, $b) {
    return $a['items'][0]['category'] <=> $b['items'][0]['category'];
  });
  foreach ($grouped_proposals as &$proposal_group) {
    usort($proposal_group['items'], function ($a, $b) {
      return $b['raw_deadline'] <=> $a['raw_deadline'];
    });
  }
  $variables['grouped_proposals'] = $grouped_proposals;
}

/**
 * Implements hook_preprocess_views_view() for ITC active proposals.
 */
function jcc_elevated_preprocess_views_view__past_proposals__block_2(&$variables) {
  jcc_elevated_preprocess_views_view__past_proposals__block_1($variables);
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
    if (isset($row['#row']) && $row['#row'] instanceof ResultRow) {
      $nid = $row['#row']->_entity->id() ?? NULL;
      if ($nid) {
        $node = Node::load($nid);
        if ($node) {
          // Render deadline date.
          $deadline = $node->get('unpublish_on')->value ? date('F j, Y', $node->get('unpublish_on')->value) : '';
          // If no deadline or 12/12/9999.
          if (!$deadline || $deadline === 'December 12, 9999') {
            $deadline = t('Open Until Filled');
          }
          $teaser = $node->get('body') ?? '';
          if ($teaser) {
            if (!empty($node->get('body')->summary)) {
              $teaser = strip_tags($node->get('body')->summary);
            }
            else {
              $content = $node->body->view('full');
              if (!empty($content['#items'])) {
                $body_content = $content['#items']->getValue()[0]['value'];
                $summary_trim_length = $content[0]['#text_summary_trim_length'] ?? 600;
                $body_summary = text_summary(strip_tags($body_content), NULL, $summary_trim_length);
                $teaser = html_entity_decode($body_summary);
              }
            }
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
      }
    }

    // Count summary for the current page of results.
    $jobs['count_summary'] = $variables['header']['result']['#markup'] ?? '';

    $variables['jobs'] = $jobs;
  }
}

/**
 * Implements hook_preprocess_views_view() for job search listing block.
 */
function jcc_elevated_preprocess_views_view__jobs__search_page(&$variables) {
  // Reuse current jobs search block as common preprocess.
  jcc_elevated_preprocess_views_view__jobs__search($variables);
}

/**
 * Implements hook_preprocess_views_view() for published opinions listing block.
 */
function jcc_elevated_preprocess_views_view__opinions__published(&$variables) {
  $entity_type_manager = \Drupal::entityTypeManager();
  $node_manager = $entity_type_manager->getStorage('node');
  $taxonomy_manager = $entity_type_manager->getStorage('taxonomy_term');

  // Customize filter UI.
  __jcc_view_results_exposed_filter_init($variables);

  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $opinions = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $node = $row['#node'];
    $post_date = $node->get('field_date_time')->value ? $node->get('field_date_time')->value : '';
    $type = $node->get('field_opinion_type')->target_id ? $taxonomy_manager->load($node->get('field_opinion_type')->target_id)->get('name')->value : '';
    $case_number = $node->get('field_case_number_plain')->value ? $node->get('field_case_number_plain')->value : '';
    $opinion_source = $node->get('field_opinion_source')->target_id ? $taxonomy_manager->load($node->get('field_opinion_source')->target_id)->get('name')->value : '';
    $review_granted = $node->get('field_review_granted')->value;
    $title = $node->get('title')->value ? $node->get('title')->value : '';
    $weight = $node->get('field_weight')->value;

    // Remote file links.
    $remote_files = [
      __jcc_opinion_file_links_builder($type, $review_granted, 'PDF', $case_number),
      [
        'url' => $node->toUrl()->setAbsolute()->toString(),
        'file_type' => t('Other Formats'),
        'aria_label' => t('Go to opinion post for case @case_number to get other document formats', ['@case_number' => $case_number]),
      ],
    ];

    // Related cases.
    $related_cases_urls = [];
    $related_cases = $node->get('field_related_cases_plain')->value;
    if ($related_cases) {
      // If space delimited by accident or from migration.
      if (strpos($related_cases, ' ')) {
        $related_cases = preg_split('/\s+/', $related_cases);
      }
      else {
        // Remove any spaces from comma delimited list.
        $related_cases = preg_replace('/\s+/', '', $related_cases);
        // Convert to array.
        $related_cases = explode(',', $related_cases);
      }
      foreach ($related_cases as $related_case) {
        $accms_url = __jcc_accms_link_builder($related_case);
        if ($accms_url) {
          $related_cases_urls[] = '<a href="' . $accms_url . '" arial_label="View related case details from  ' . $related_case . '">' . $related_case . '</a>';
        }
      }
    }

    // Citings.
    $citings = NULL;
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('field_case_number_plain', $case_number, 'CONTAINS');
    $query->exists('field_media_file_multiple');
    $citing_archive = $query->execute();
    if ($citing_archive) {
      $citing_node_id = reset($citing_archive);
      $citing_node = $node_manager->load($citing_node_id);
      $citing_url = $citing_node->toUrl()->toString();
      $citings = '<a href="' . $citing_url . '" aria_label="View citings archive for ' . $case_number . '">View citings archive</a>';
    }

    $opinion = [
      'type' => $type,
      'case_number' => t('Case:') . ' ' . $case_number ? $case_number : t('Unspecified'),
      'timestamp' => $post_date ? strtotime($post_date) : '',
      'grouping' => $post_date ? \Drupal::service('date.formatter')->format(strtotime($post_date), 'custom', 'Ymd') : '',
      'posted' => $post_date ? \Drupal::service('date.formatter')->format(strtotime($post_date), 'custom', 'F j, Y') : '',
      'source' => $opinion_source,
      'review_granted' => $review_granted,
      'title' => [
        'text' => $title ?? '',
        'url' => $case_number && $opinion_source != 'Appellate Division' ? __jcc_accms_link_builder($case_number) : NULL,
      ],
      'remote_files' => $remote_files,
      'related_cases' => $related_cases_urls ? implode(' ', $related_cases_urls) : '',
      'citings' => $citings,
      'weight' => $weight,
    ];
    $opinions[] = $opinion;
  }

  // Prioritize Supreme Court opinions (weight) per day.
  $sort = [];
  foreach ($opinions as $k => $v) {
    $sort['grouping'][$k] = $v['grouping'];
    $sort['weight'][$k] = $v['weight'];
    $sort['timestamp'][$k] = $v['timestamp'];
  }
  array_multisort($sort['grouping'], SORT_DESC, $sort['weight'], SORT_ASC, $sort['timestamp'], SORT_DESC, $opinions);

  $variables['opinions'] = $opinions;
}

/**
 * Implements hook_preprocess_views_view() for published opinions review granted listing block.
 */
function jcc_elevated_preprocess_views_view__opinions__published_review_granted(&$variables) {
  // Reuse current published opinions search block as common preprocess.
  jcc_elevated_preprocess_views_view__opinions__published($variables);
}

/**
 * Implements hook_preprocess_views_view() for extended published opinions listing block.
 */
function jcc_elevated_preprocess_views_view__opinions__published_extended(&$variables) {
  // Reuse current published opinions search block as common preprocess.
  jcc_elevated_preprocess_views_view__opinions__published($variables);
}

/**
 * Implements hook_preprocess_views_view() for unpublished opinions listing block.
 */
function jcc_elevated_preprocess_views_view__opinions__unpublished(&$variables) {
  // Reuse current published opinions search block as common preprocess.
  jcc_elevated_preprocess_views_view__opinions__published($variables);
}

/**
 * Implements hook_preprocess_views_view() for unpublished opinions listing review granted block.
 */
function jcc_elevated_preprocess_views_view__opinions__unpublished_review_granted(&$variables) {
  // Reuse current published opinions search block as common preprocess.
  jcc_elevated_preprocess_views_view__opinions__published($variables);
}

/**
 * Implements hook_preprocess_views_view() for published opinions citings archive search block.
 */
function jcc_elevated_preprocess_views_view__opinions__citings_archive(&$variables) {
  $entity_type_manager = \Drupal::service('entity_type.manager');
  $taxonomy_manager = $entity_type_manager->getStorage('taxonomy_term');

  // Customize filter UI.
  __jcc_view_results_exposed_filter_init($variables);

  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $opinions = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $node = $row['#node'];
    $post_date = $node->get('field_date_time')->value ? $node->get('field_date_time')->value : '';
    $case_number = $node->get('field_case_number_plain')->value ? $node->get('field_case_number_plain')->value : '';
    $case_source = $node->get('field_opinion_source')->target_id ? $taxonomy_manager->load($node->get('field_opinion_source')->target_id)->get('name')->value : '';

    // Citings.
    $citings = [];
    if ($node->get('field_media_file_multiple')->getValue()) {
      foreach ($node->get('field_media_file_multiple')->getValue() as $citing) {
        $media = Media::load($citing['target_id']);
        $file = File::load($media->get('field_media_file')->target_id);
        if ($file) {
          $citing_title = $media->get('field_media_text')->value;
          if (is_null($citing_title)) {
            $citing_title = $media->get('name')->value;
          }
          $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->get('uri')->value);
          $citings[] = [
            'text' => ['#markup' => '<a href="' . $file_url . '" aria_label="Download or view citing ' . $citing_title . '">' . $citing_title . '</a>'],
          ];
        }
      }
    }

    $opinion = [
      'case_number' => t('Case:') . ' ' . $case_number ? $case_number : t('Unspecified'),
      'posted' => $post_date ? \Drupal::service('date.formatter')->format(strtotime($post_date), 'custom', 'F j, Y') : '',
      'year' => $post_date ? \Drupal::service('date.formatter')->format(strtotime($post_date), 'custom', 'Y') : '',
      'source' => $case_source,
      'title' => [
        'text' => $node->get('title')->value ?? '',
        'url' => $case_number && $case_source != 'Appellate Division' ? __jcc_accms_link_builder($case_number) : NULL,
      ],
      'citings' => $citings,
    ];

    $opinions[] = $opinion;
  }

  // Group opinions by year.
  $grouped_opinions = [];
  foreach ($opinions as $item) {
    if ($item['posted']) {
      $year = $item['year'];
      $grouped_opinions[$year][] = $item;
    }
  }

  $variables['opinions'] = $grouped_opinions;
}

/**
 * Implements hook_preprocess_views_view() for published opinions listing page.
 */
function jcc_elevated_preprocess_views_view__opinions__all_page(&$variables) {
  // Reuse current published opinions search block as common preprocess.
  jcc_elevated_preprocess_views_view__opinions__published($variables);
}

/**
 * Implements hook_preprocess_HOOK() for Requests(RPF) listing.
 */
function jcc_elevated_preprocess_views_view__requests__requests_listing_active(&$variables): void {
  // Customize filter UI.
  __jcc_view_results_exposed_filter_init($variables);

  // Reset our embedded view exposed form to use the current page as the action.
  // This is necessary because it is not an ajax controlled element, so it
  // triggers a page refresh to the views default page (which is an admin page).
  $variables['exposed']['#action'] = \Drupal::request()->getPathInfo();

  // Process the results for the search api view.
  $variables['processed_rows'] = [];
  if (isset($variables['rows'][0]['#rows'])) {
    foreach ($variables['rows'] as $group) {
      foreach ($group['#rows'] as $item) {
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
}

/**
 * Implements hook_preprocess_views_view() for requests_listing_archived block.
 */
function jcc_elevated_preprocess_views_view__requests__requests_listing_archived(&$variables): void {
  // Customize filter UI.
  __jcc_view_results_exposed_filter_init($variables);

  // Reset our embedded view exposed form to use the current page as the action.
  // This is necessary because it is not an ajax controlled element, so it
  // triggers a page refresh to the views default page (which is an admin page).
  $variables['exposed']['#action'] = \Drupal::request()->getPathInfo();

  // Process the results for the search api view.
  $variables['processed_rows'] = [];
  if (isset($variables['rows'][0]['#rows'])) {
    foreach ($variables['rows'] as $group) {
      foreach ($group['#rows'] as $item) {
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
        ];
        foreach ($items as $key => $value) {
          if (!empty($value)) {
            if ($key == 'secondary') {
              $value['#context']['value'] = t('No.') . ' ' . $value['#context']['value'];
            }
            $brow[$key] = $value;
          }
        }

        $variables['processed_rows'][] = [
          'brow' => $brow,
          'title' => $request->label(),
          'url' => $request->toUrl(),
        ];
      }
    }
  }
}

/**
 * Implements hook_preprocess_views_view().
 */
function jcc_elevated_preprocess_views_view__restricted_content__block_2(&$variables): void {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $results = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $entity_result = $row['#row'];
    $entity_type = $entity_result->_entity->getEntityTypeId();
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $taxonomy_type_manager = $entity_type_manager->getStorage('taxonomy_term');
    $file_manager = $entity_type_manager->getStorage('file');

    if ($entity_type == 'node') {
      $node = $entity_type_manager->getStorage('node')->load($entity_result->nid);
      $subject = $taxonomy_type_manager->load($node->get('field_subject_matter')->target_id);
      $topic = $taxonomy_type_manager->load($node->get('field_category')->target_id);

      $results[] = [
        'title' => $node->getTItle(),
        'subject' => $subject ? $subject->get('name')->value : '',
        'topic' => $topic ? $topic->get('name')->value : '',
      ];
    }
    elseif ($entity_type == 'media') {
      $media = $entity_type_manager->getStorage('media')->load($entity_result->mid);

      // Check media access permissions.
      $granted = FALSE;
      $current_user = \Drupal::currentUser();
      $file = $file_manager->load($media->get('field_media_file_multiple')[0]->target_id);
      $media_usage = \Drupal::service('file.usage')->listUsage($file);
      if ($media_usage) {
        $media_id = ($media_usage['file']['media']);
        $media_id = key($media_id);
        $media = \Drupal::entityTypeManager()->getStorage('media')->load($media_id);
        if ($media) {
          // Determine where the media is being referenced.
          // Deny access if referenced anywhere restricted.
          $node_usage = \Drupal::service('entity_usage.usage')->listSources($media);
          if ($node_usage) {
            // Check node access using the latest revision.
            $granted = FALSE;

            foreach (reset($node_usage) as $node_id => $revisions) {
              $node = \Drupal::entityTypeManager()->getStorage('node')->load($node_id);

              foreach ($revisions as $revision) {
                if ($revision['source_vid'] == $node->getRevisionId()) {
                  if ($node->access('view', $current_user)) {
                    // Stop checking when access is true.
                    $granted = TRUE;
                    continue 2;
                  }
                }
              }
            }
          }
        }
      }

      if ($granted) {
        $subject = $taxonomy_type_manager->load($media->get('field_subject_matter')->target_id);
        $topic = $taxonomy_type_manager->load($media->get('field_topic')->target_id);
        $results[] = [
          'title' => $media->getName(),
          'subject' => $subject ? $subject->get('name')->value : '',
          'topic' => $topic ? $topic->get('name')->value : '',
        ];
      }
    }
  }

  // Results sorted by subject and by topic.
  $results_sorted = [];
  foreach ($results as $result) {
    if ($result['topic']) {
      $result_subject = $result['subject'];
      $result_topic = $result['topic'];
      $results_sorted[$result_subject][$result_topic][] = $result;
    }
  }

  $variables['results'] = $results_sorted;
}

/**
 * Implements hook_preprocess_views_view().
 */
function jcc_elevated_preprocess_views_view__restricted_content__block_3(&$variables): void {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $results = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $entity_result = $row['#row'];
    $entity_type = $entity_result->_entity->getEntityTypeId();
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $taxonomy_type_manager = $entity_type_manager->getStorage('taxonomy_term');
    $file_manager = $entity_type_manager->getStorage('file');

    if ($entity_type == 'node') {
      $node = $entity_type_manager->getStorage('node')->load($entity_result->nid);
      $subject = $taxonomy_type_manager->load($node->get('field_subject_matter')->target_id);
      $topic = $taxonomy_type_manager->load($node->get('field_category')->target_id);

      $results[] = [
        'title' => $node->getTItle(),
        'subject' => $subject ? $subject->get('name')->value : '',
        'topic' => $topic ? $topic->get('name')->value : '',
      ];
    }
    elseif ($entity_type == 'media') {
      $media = $entity_type_manager->getStorage('media')->load($entity_result->mid);

      // Check media access permissions.
      $granted = FALSE;
      $current_user = \Drupal::currentUser();
      $file = $file_manager->load($media->get('field_media_file_multiple')[0]->target_id);
      $media_usage = \Drupal::service('file.usage')->listUsage($file);
      if ($media_usage) {
        $media_id = ($media_usage['file']['media']);
        $media_id = key($media_id);
        $media = \Drupal::entityTypeManager()->getStorage('media')->load($media_id);
        if ($media) {
          // Determine where the media is being referenced.
          // Deny access if referenced anywhere restricted.
          $node_usage = \Drupal::service('entity_usage.usage')->listSources($media);
          if ($node_usage) {
            // Check node access using the latest revision.
            $granted = FALSE;

            foreach (reset($node_usage) as $node_id => $revisions) {
              $node = \Drupal::entityTypeManager()->getStorage('node')->load($node_id);

              foreach ($revisions as $revision) {
                if ($revision['source_vid'] == $node->getRevisionId()) {
                  if ($node->access('view', $current_user)) {
                    // Stop checking when access is true.
                    $granted = TRUE;
                    continue 2;
                  }
                }
              }
            }
          }
        }
      }

      if ($granted) {
        $subject = $taxonomy_type_manager->load($media->get('field_subject_matter')->target_id);
        $topic = $taxonomy_type_manager->load($media->get('field_topic')->target_id);
        $results[] = [
          'title' => $media->getName(),
          'subject' => $subject ? $subject->get('name')->value : '',
          'topic' => $topic ? $topic->get('name')->value : '',
        ];
      }
    }
  }

  // Results sorted by subject and by topic.
  $results_sorted = [];
  foreach ($results as $result) {
    if ($result['topic']) {
      $result_topic = $result['topic'];
      $results_sorted[$result_topic][] = $result;
    }
  }

  $variables['results'] = $results_sorted;
}

/**
 * Implements hook_preprocess_HOOK() for Requests(RPF) archived items listing.
 */
function jcc_elevated_preprocess_views_view__requests__requests_grouped_archived(&$variables): void {
  __jcc_view_results_exposed_filter_init($variables);

  // Reset our embedded view exposed form to use the current page as the action.
  // This is necessary because it is not an ajax controlled element, so it
  // triggers a page refresh to the views default page (which is an admin page).
  $variables['exposed']['#action'] = \Drupal::request()->getPathInfo();

  // Process the results for the search api view.
  $variables['processed_rows'] = [];
  if (isset($variables['rows'][0]['#rows'])) {
    foreach ($variables['rows'] as $group) {
      $list_label = trim($group['#title']);
      $variables['processed_rows'][$list_label]['list_label'] = $list_label;
      foreach ($group['#rows'] as $delta => $item) {
        $request = $item['#row']->_entity;
        $brow = [];
        $variables['processed_rows'][$list_label]['items'][$delta] = [
          'brow' => $brow,
          'title' => $request->label(),
          'url' => $request->toUrl(),
          'item_classes' => ['result-extra-style__heading__lg'],
        ];
      }
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
    if (isset($form['items_per_page'])) {
      $form['items_per_page']['#attributes']['form'] = $form['#id'];
    }
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

  // Alter the Archived Requests exposed form.
  if (($view->id() == 'requests') && ($view->current_display == 'requests_grouped_archived')) {
    // Remove captcha from search form.
    unset($form['captcha']);
    unset($form['field_rfp_end_date_value']['#options']['All']);
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
 * Helper function to apply common exposed filter view customizations.
 */
function __jcc_view_results_exposed_filter_init(&$variables) {
  $form = &$variables['exposed'];

  // Remove captcha.
  unset($form['captcha']);

  // Set the placeholder and Select all text.
  foreach (Element::children($form) as $id) {
    $placeholder = $form['#info']['filter-' . $id]['label'] ?? '';
    $form[$id]['#attributes']['placeholder'] = $placeholder;
    if (isset($form[$id]['#type']) && $id == 'items_per_page') {
      // Do nothing.
    }
    elseif (isset($form[$id]['#type']) && $form[$id]['#type'] == 'select') {
      // Set label as 'select all' option label.
      $form[$id]['#attributes']['placeholder'] = NULL;
      if ($id != 'jcc_section' && isset($form[$id]['#options']['All'])) {
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

  // Items per page filter. All items per page elements need to live outside the
  // views exposed form.
  if (isset($variables['rows'][0]['#rows']) && isset($variables['exposed']['items_per_page'])) {

    // Create our new variable for the templates.
    $variables['items_per_page'] = $variables['exposed']['items_per_page'];

    // Since the items_per_page element is moved outside the form tag, we need
    // to apply the original form id to the form attribute of the element.
    if ($variables['exposed']['#id']) {
      $variables['items_per_page']['#attributes']['form'] = $variables['exposed']['#id'];
    }

    // Rewrite our items_per_page options to "Show XX per page".
    foreach ($variables['items_per_page']['#options'] as $key => $value) {
      $variables['items_per_page']['#options'][$key] = 'Show ' . $key . ' per page';
    }

    // Remove the label and remove the item from the exposed form.
    unset($variables['items_per_page']['#title']);
    unset($variables['exposed']['items_per_page']);
  }
  if (empty($variables['view']->result)) {
    unset($variables['exposed']['items_per_page']);
  }
  // Count summary for the current page of results.
  // Suppress duplicate summary with empty header in ViewsResults twig.
  $variables['count_summary'] = $variables['header']['result']['#markup'] ?? '';
}
