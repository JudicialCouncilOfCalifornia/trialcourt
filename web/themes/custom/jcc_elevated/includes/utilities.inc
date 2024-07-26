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
function __jcc_elevated_determine_date_range_output($date): null|MarkupInterface {
  if (!$date) {
    return NULL;
  }

  $separator_item = $date['separator']['#plain_text'] ?? ' - ';
  [$start_day, $start_time] = explode($separator_item, $date['start_date']['#text']);
  [$end_day, $end_time] = explode($separator_item, $date['end_date']['#text']);

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
  $start_datetime_attr = $date['start_date']['#attributes']['datetime'];
  $end_datetime_attr = $date['end_date']['#attributes']['datetime'];
  return Markup::create("<time datetime='$start_datetime_attr'>" . $date['start_date']['#text'] . "</time>" . $separator . "<time datetime='$end_datetime_attr'>" . $date['end_date']['#text'] . "</time>");
}

/**
 * Helper to determine how a datetime string should be output.
 *
 * @param null|array $datetime
 *   Array of time theme data.
 *
 * @return null|\Drupal\Component\Render\MarkupInterface
 *   Return text.
 */
function __jcc_elevated_determine_datetime_output($datetime): null|MarkupInterface {
  if (!$datetime) {
    return NULL;
  }

  $separator_item = $date['separator']['#plain_text'] ?? ' - ';
  [$date, $time] = explode($separator_item, $datetime['#text']);

  $all_day = $time == "12:00 AM"||$time == "11:59 PM";

  if (!$all_day) {
    $datetime['#text'] = $date . '<br><small>' . $time . '</small>';
  }
  else {
    $datetime['#text'] = $date;
  }

  $datetime_attr = $datetime['#attributes']['datetime'];
  return Markup::create("<time datetime='$datetime_attr'>" . $datetime['#text'] . "</time>");

}
