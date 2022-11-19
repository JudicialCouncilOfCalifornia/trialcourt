<?php

/**
 * @file
 * Functions for views processing.
 */

use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function jcc_components_theme_suggestions_views_exposed_form_alter(array &$suggestions, array $variables) {
  if (strpos($variables['form']['#id'], 'views-exposed-form-') >= 0) {
    $form = str_replace('views-exposed-form-', '', $variables['form']['#id']);
    $form = str_replace('-', '_', $form);

    $suggestions[] = $variables['theme_hook_original'] . '__' . $form;
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function jcc_components_theme_suggestions_views_view_alter(array &$suggestions, array $variables) {
  // Add a suggestion based on the view name and the current display.
  $view = $variables['view'];
  $name = $view->id();
  $display = $view->current_display;
  $suggestions[] = 'views_view__' . $name;
  $suggestions[] = 'views_view__' . $name . '__' . $display;
}

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_views_view(&$variables) {
  $variables['header_footer_variant'] = theme_get_setting('header_footer_variant', 'jcc_components');

  if ($variables['id'] == 'search') {
    $variables['keywords'] = $variables['exposed']['search']['#value'];
    $variables['num_results'] = $variables['view']->total_rows;
  }
}

/**
 * Implements hook_preprocess_HOOK() for news + sticky listing.
 */
function jcc_components_preprocess_views_view__news__sticky_list(&$variables) {
  if ($variables['view']->args && $variables['view']->args[0] != 'all') {
    $taxonomy_id = $variables['view']->args[0];
    $variables['more_custom_title'] = 'See All ' . Term::load($taxonomy_id)->get('name')->value . 's';
    $variables['more_custom_url'] = '/views/news?search=&type[' . $taxonomy_id . ']=' . $taxonomy_id . '&date_start=&date_end=';
  }
}

/**
 * Implements hook_preprocess_HOOK() for news listing.
 */
function jcc_components_preprocess_views_view__news__news_list(&$variables) {
  // Translation via TMGMT breaks dates where translators entered invlid date
  // values in date fields. If date is invalid clear it before template renders.
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $node = $row['#node'];
    if (!jcc_components_validate_date($node->field_date->value)) {
      $node->field_date->value = '';
    }
  }
}

/**
 * Helpfer function to validate a date string.
 *
 * @param string $date
 *   The string to validate.
 * @param string $format
 *   The optional date format to test.
 *
 * @return bool
 *   TRUE if the string is a valid date format, else FALSE.
 */
function jcc_components_validate_date($date, $format = 'Y-m-d') {
  $d = DateTime::createFromFormat($format, $date);
  return $d && $d->format($format) === $date;
}

/**
 * Implements hook_preprocess_HOOK() for events full listing.
 */
function jcc_components_preprocess_views_view__events__events_list(&$variables) {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $events = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $row = $row['#node'];
    $nid = $row->id();
    $startDate = $row->field_date_range->start_date;
    $endDate = $row->field_date_range->end_date;

    $event = [
      'title' => $row->title->value,
      'url' => Url::fromRoute('entity.node.canonical', ['node' => $nid], ['absolute' => FALSE])->toString(),
      'type' => $row->field_event_type->target_id ? Term::load($row->field_event_type->target_id)->get('name')->value : '',
      'start_date' => $startDate,
      'end_date' => $endDate,
      'summary' => $row->body->view('teaser'),
    ];
    array_push($events, $event);
  }

  $variables['rows'] = $events;
}

/**
 * Implements hook_preprocess_HOOK() for oral arguments case documents listing.
 */
function jcc_components_preprocess_views_view__oral_arguments__block_1(&$variables) {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $docs = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $row = $row['#node'];
    $title = $row->title->value;
    if ($row->field_verbose_title->value) {
      $title = $row->field_verbose_title->value;
    }

    $media = Media::load($row->field_media->target_id);
    if (isset($media) && $media->field_media_file->target_id) {
      $file = File::load($media->field_media_file->target_id);
      $filename = $file->get('filename')->value;
      // File order weight through file naming convention.
      $file_weight = explode('-', $filename);
    }
    else {
      $file = NULL;
      $filename = NULL;
      $file_weight = "0";
    }
    $case = Term::load($row->field_case->target_id);

    $doc = [
      'case' => [
        'name' => $case && $case->get('name')->value ? $case->get('name')->value : 'NO CASE ASSIGNED',
        'description' => $case && $case->get('description')->value ? $case->get('description')->value : NULL,
        'weight' => $case && $case->get('field_weight')->value ? $case->get('field_weight')->value : 0,
      ],
      'title' => $title,
      'url' => $file ? $file->createFileUrl() : NULL,
      'filing_date' => $row->field_date->date ? $row->field_date->date->format('F j, Y') : 'Undefined',
      'file_weight' => $file_weight ? $file_weight[0] : 0,
      // Contextual edit document option.
      'edit_option' => _jcc_components_can_edit_entity($row, t('Edit')),
    ];
    array_push($docs, $doc);
  }

  // Sort documents by weight.
  usort($docs, function ($a, $b) {
    return strnatcmp($a['file_weight'], $b['file_weight']);
  });

  // Group documents by case.
  $cases = [];
  foreach ($docs as $item) {
    if ($item['case']['name']) {
      $case = $item['case']['name'];
      $cases[$case]['weight'] = $item['case']['weight'];
      $cases[$case]['docs'][] = $item;
    }
  }

  // Sort cases by weight.
  uasort($cases, function ($a, $b) {
    return ($a['weight'] <=> $b['weight']);
  });

  $variables['items'] = $cases;
}

/**
 * Implements hook_preprocess_HOOK() for oral arguments briefs listing.
 */
function jcc_components_preprocess_views_view__oral_arguments__block_2(&$variables) {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $pages = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $row = $row['#node'];
    $page = [
      'year' => $row->field_date->date ? $row->field_date->date->format('Y') : 'Undefined',
      'link' => $row->toLink(NULL, 'canonical', ['absolute' => TRUE]),
    ];
    array_push($pages, $page);
  }
  $briefs = [];
  foreach ($pages as $item) {
    if ($item['year']) {
      $year = $item['year'];
      $briefs[$year][] = $item;
    }
  }
  $variables['items'] = $briefs;
}

/**
 * Implements hook_preprocess_HOOK() for minutes.
 */
function jcc_components_preprocess_views_view__case_information__block_1(&$variables) {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $current_year = date('Y');
  $pages = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $row = $row['#node'];
    $doc_year = $row->field_date->date ? $row->field_date->date->format('Y') : 'Undefined';
    $years_past = $current_year - $doc_year;
    if ($years_past < 3) {
      $media = Media::load($row->field_media->target_id);
      $file = File::load($media->field_media_file->target_id);
      $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->uri->value);
      $page = [
        'year' => $doc_year,
        'url' => \Drupal::service('file_url_generator')->transformRelative($file_url),
        'filing_date' => $row->field_date->date ? $row->field_date->date->format('F j, Y') : 'Undefined',
      ];
      array_push($pages, $page);
    }
  }

  $minutes = [];
  foreach ($pages as $item) {
    if ($item['year']) {
      $year = $item['year'];
      $minutes[$year][] = $item;
    }
  }
  $variables['items'] = $minutes;
}

/**
 * Implements hook_preprocess_HOOK() for weekly case summaries.
 */
function jcc_components_preprocess_views_view__case_information__block_2(&$variables) {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $current_year = date('Y');
  $pages = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $row = $row['#node'];
    $doc_year = $row->field_date->date ? $row->field_date->date->format('Y') : 'Undefined';
    $years_past = $current_year - $doc_year;
    if ($years_past < 3) {
      $media = Media::load($row->field_media->target_id);
      $file = File::load($media->field_media_file->target_id);
      $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->uri->value);
      $page = [
        'year' => $doc_year,
        'url' => \Drupal::service('file_url_generator')->transformRelative($file_url),
        'filing_date' => $row->field_date->date ? $row->field_date->date->format('F j, Y') : 'Undefined',
      ];
      array_push($pages, $page);
    }
  }

  $summaries = [];
  foreach ($pages as $item) {
    if ($item['year']) {
      $year = $item['year'];
      $summaries[$year][] = $item;
    }
  }
  $variables['items'] = $summaries;
}

/**
 * Implements hook_preprocess_HOOK() for weekly conference results.
 */
function jcc_components_preprocess_views_view__case_information__block_3(&$variables) {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $current_year = date('Y');
  $pages = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $row = $row['#node'];
    $doc_year = $row->field_date->date ? $row->field_date->date->format('Y') : 'Undefined';
    $years_past = $current_year - $doc_year;
    if ($years_past < 3) {
      $media = Media::load($row->field_media->target_id);
      $file = File::load($media->field_media_file->target_id);
      $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->uri->value);
      $page = [
        'year' => $doc_year,
        'url' => \Drupal::service('file_url_generator')->transformRelative($file_url),
        'filing_date' => $row->field_date->date ? $row->field_date->date->format('F j, Y') : 'Undefined',
      ];
      array_push($pages, $page);
    }
  }

  $results = [];
  foreach ($pages as $item) {
    if ($item['year']) {
      $year = $item['year'];
      $results[$year][] = $item;
    }
  }
  $variables['items'] = $results;
}

/**
 * Implements hook_preprocess_HOOK() for calendar results.
 */
function jcc_components_preprocess_views_view__case_information__block_4(&$variables) {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $results = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $row = $row['#node'];
    $media = Media::load($row->field_media->target_id);
    $file = File::load($media->field_media_file->target_id);
    $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->uri->value);
    // BEGIN - Create amended identifier from filename.
    $file_name = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file->filename->value);
    $file_amended = NULL;
    if (preg_match("/^[a-z]$/i", substr($file_name, -1))) {
      $file_amended = strtolower(substr($file_name, -1));
    }
    // END.
    $notes = strip_tags($row->body->processed, '<strong><em><a>');
    $result = [
      'title' => $row->title->value,
      'amended_code' => $file_amended,
      'url' => \Drupal::service('file_url_generator')->transformRelative($file_url),
      'hearing_date' => $row->field_date_range->start_date,
      'publish_date' => $row->field_date->date ? $row->field_date->date->format('m/d/Y') : 'Undefined',
      'notes' => $notes,
      'edit_option' => _jcc_components_can_edit_entity($row, t('Edit')),
    ];
    array_push($results, $result);
  }

  $variables['items'] = $results;
}

/**
 * Implements hook_preprocess_HOOK() for short document listing.
 */
function jcc_components_preprocess_views_view__documents__default(&$variables) {
  // Get latest Oral Arguments page for docs.
  $latest_doc = $variables['rows'][0]['#rows'][0]['#node'];
  $hearing_date = $latest_doc->field_date_range->start_date;
  $term_id = $latest_doc->field_document_type ? $latest_doc->field_document_type->target_id : NULL;

  if (Term::load($term_id)->get('name')->value == 'Oral Argument') {
    $nids = \Drupal::entityQuery('node')->condition('type', 'subpage')->execute();
    $nodes = Node::loadMultiple($nids);

    $oral_args = [];
    foreach ($nodes as $node) {
      if ($node->field_category->target_id == $term_id) {
        array_push($oral_args, $node);
      }
    }

    // Sort pages by publication date field.
    uasort($oral_args, function ($a, $b) {
      return strnatcmp($b->field_date->date->format('Ymd'), $a->field_date->date->format('Ymd'));
    });

    // Return published oral arguments if available.
    $latest_oa = array_slice($oral_args, 0, 1);
    if ($latest_oa && str_contains($latest_oa[0]->label(), $hearing_date->format('F j')) && str_contains($latest_oa[0]->label(), strval(date('Y')))) {
      $variables['args_url'] = $latest_oa[0]->toUrl()->toString();
    }
  }
}
