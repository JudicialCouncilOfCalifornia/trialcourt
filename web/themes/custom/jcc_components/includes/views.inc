<?php

/**
 * @file
 * Functions for views processing.
 */

use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Term;

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
 * Implements hook_preprocess_HOOK() for oral arguments case documents listing.
 */
function jcc_components_preprocess_views_view__oral_arguments__default(&$variables) {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $node = \Drupal::routeMatch()->getParameter('node');
  if ($node instanceof NodeInterface) {
    foreach ($node->field_components as $component) {
      $para = Paragraph::load($component->target_id);
      if (isset($para->field_content_stream)) {
        $start_date = $para->field_filter_by_date->start_date->format('Ydm');
        $end_date = $para->field_filter_by_date->end_date->format('Ydm');
        $date_range = $start_date . ' - ' . $end_date;
      }
    }
  }

  $docs = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $row = $row['#node'];
    $hearing_start_date = $row->field_date_range->start_date->format('Ydm');
    // Migration could exclude hearing end date so assume single day.
    $hearing_end_date = $hearing_start_date;
    if ($row->field_date_range->end_date) {
      $hearing_end_date = $row->field_date_range->end_date->format('Ydm');
    }
    $hearing_range_date = $hearing_start_date . ' - ' . $hearing_end_date;
    if (isset($hearing_range_date) && $hearing_range_date == $date_range) {
      $media = Media::load($row->field_media->target_id);
      $file = File::load($media->field_media_file->target_id);
      $filename = $file->get('filename')->value;
      // File order weight through file naming convention.
      $file_weight = explode('-', $filename);
      $case = Term::load($row->field_case->target_id);
      $doc = [
        'case' => [
          'name' => $case && $case->get('name')->value ? $case->get('name')->value : 'NO CASE ASSIGNED',
          'description' => $case && $case->get('description')->value ? $case->get('description')->value : NULL,
          'weight' => $case && $case->get('field_weight')->value ? $case->get('field_weight')->value : 0,
        ],
        'title' => $row->title->value,
        'url' => $file ? $file->createFileUrl() : NULL,
        'filing_date' => $row->field_date->date ? $row->field_date->date->format('F j, Y') : 'Undefined',
        'file_weight' => $file_weight[0],
      ];
      array_push($docs, $doc);
    }
  }

  // Sort cases and documents by weight.
  usort($docs, function ($a, $b) {
    return ($a['file_weight'] <=> $b['file_weight']) * 1000 + ($a['case']['weight'] <=> $b['case']['weight']) * 100;
  });

  $cases = [];
  foreach ($docs as $item) {
    if ($item['case']['name']) {
      $case = $item['case']['name'];
      $cases[$case][] = $item;
    }
  }
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
      $page = [
        'year' => $doc_year,
        'url' => file_create_url($file->uri->value),
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
      $page = [
        'year' => $doc_year,
        'url' => file_create_url($file->uri->value),
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
      $page = [
        'year' => $doc_year,
        'url' => file_create_url($file->uri->value),
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
