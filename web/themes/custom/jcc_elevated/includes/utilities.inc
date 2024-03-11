<?php

/**
 * @file
 * Functions for views processing.
 */

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
