<?php

/**
 * @file
 * Functions for views processing.
 */

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Render\Markup;
use Drupal\taxonomy\Entity\Term;

/**
 * Helper function to load language labels.
 *
 * @return string
 *   Language labels JSON.
 */
function __jcc_language_sets() {
  $data = file_get_contents(\Drupal::service('extension.path.resolver')->getPath('theme', 'jcc_elevated') . '/jcc-language-labels.json');

  return json_decode($data, TRUE);
}

/**
 * Helper function to get comma delimited job locations.
 */
function __jcc_get_job_locations($node) {
  $locations = [];
  foreach ($node->get('field_job_location') as $location) {
    $location = $location->target_id ? Term::load($location->target_id)->get('name')->value : '';
    $locations[] = $location;
  }
  // To comma delimited string.
  $locations = implode(', ', $locations);
  // Apply 'or' conjunction if needed.
  $locations_count = substr_count($locations, ',');
  if ($locations_count >= 2) {
    $locations = substr_replace($locations, ', or ', strrpos($locations, ','), 1);
  }
  elseif ($locations_count === 1) {
    $locations = preg_replace('/, /', ' or ', trim($locations));
  }

  return $locations;
}

/**
 * Helper to determine how a daterange string should be output.
 *
 * @param null|array $date
 *   Array of start_date, separator, and end_date information.
 *
 * @return null|\Drupal\Component\Render\MarkupInterface
 *   Return text.
 */
function __jcc_elevated_determine_date_output($date): null|MarkupInterface {
  if (!$date) {
    return NULL;
  }

  [$start_day, $start_time] = explode($date['separator']['#plain_text'], $date['start_date']['#text']);
  [$end_day, $end_time] = explode($date['separator']['#plain_text'], $date['end_date']['#text']);

  $same_day = $start_day == $end_day;
  $all_day = $start_time == "12:00 AM" && $end_time == "11:59 PM";

  if ($same_day && !$all_day) {
    $date['start_date']['#text'] = $start_day;
    $date['end_date']['#text'] = '<small>' . $start_time . ' - ' . $end_time . '</small>';
    $separate = FALSE;
  }
  elseif ($same_day && $all_day) {
    $date['start_date']['#text'] = $start_day;
    $date['end_date']['#text'] = FALSE;
    $separate = FALSE;
  }
  elseif (!$same_day && $all_day) {
    $date['start_date']['#text'] = $start_day;
    $date['end_date']['#text'] = $end_day;
    $date['separator']['#plain_text'] = ' to ';
    $separate = TRUE;
  }
  elseif (!$same_day && !$all_day) {
    $date['start_date']['#text'] = $start_day . '<br><small>' . $start_time . '</small>';
    $date['end_date']['#text'] = $end_day . '<br><small>' . $end_time . '</small>';
    $date['separator']['#plain_text'] = ' to ';
    $separate = TRUE;
  }
  else {
    $date['separator']['#plain_text'] = ' - ';
    $separate = FALSE;
  }

  $separator = $separate ? '<br><small>' . $date['separator']['#plain_text'] . '</small><br>' : '<br>';
  return Markup::create($date['start_date']['#text'] . $separator . $date['end_date']['#text']);
}
