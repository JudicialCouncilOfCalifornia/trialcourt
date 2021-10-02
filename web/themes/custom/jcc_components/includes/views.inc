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
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_views_view__oral_arguments__block(&$variables) {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $node = \Drupal::routeMatch()->getParameter('node');
  if ($node instanceof NodeInterface) {
    foreach ($node->field_components as $component) {
      $para = Paragraph::load($component->target_id);
      if (isset($para->field_content_stream)) {
        $start_date = $para->field_filter_by_date->start_date->format('F j, Y');
        $end_date = $para->field_filter_by_date->end_date->format('F j, Y');
        $date_range = $start_date . '-' . $end_date ? $end_date : NULL;
      }
    }
  }

  $docs = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $row = $row['#node'];
    $hearing_start_date = $row->field_date_range->start_date->format('F j, Y');
    $hearing_end_date = $row->field_date_range->end_date ? $row->field_date_range->end_date->format('F j, Y') : NULL;
    if ($hearing_end_date) {
      $hearing_range_date = $hearing_start_date . ' - ' . $hearing_end_date;
    }
    if ($hearing_start_date == $start_date || (isset($hearing_range_date) && $hearing_range_date == $date_range)) {
      $media = Media::load($row->field_media->target_id);
      $file = File::load($media->mid[0]->value);
      $case = Term::load($row->field_case->target_id);
      $doc = [
        'case' => [
          'name' => $case->get('name')->value,
          'description' => $case->get('description')->value,
          'weight' => $case->get('weight')->value,
        ],
        'title' => $row->title->value,
        'url' => $file ? $file->createFileUrl() : NULL,
        'filing_date' => $row->field_date->date->format('F j, Y'),
        'hearing_date_start' => $hearing_start_date,
        'hearing_data_range' => $hearing_range_date ? $hearing_range_date : NULL,
      ];
      array_push($docs, $doc);
    }
  }

  /**
   * Group docs by case.
   */
  function cmp($b, $a) {
    return strcmp($b['case']['weight'], $a['case']['weight']);
  }

  usort($docs, 'cmp');

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
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_views_view__oral_arguments__block_5(&$variables) {
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
