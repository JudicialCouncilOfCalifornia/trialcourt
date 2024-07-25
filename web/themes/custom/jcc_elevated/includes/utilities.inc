<?php

/**
 * @file
 * Functions for views processing.
 */

use Drupal\taxonomy\Entity\Term;
use GuzzleHttp\Exception\RequestException;

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
 * Helper function to map case numbers to ACCMS dist ids.
 */
function __jcc_accms_link_builder($case_number) {
  // Exclude any trailing opinion revision code.
  $case_number = $string = substr($case_number, 0, 7);
  // Court id is first character from case number.
  $court_id = substr($case_number, 0, 1);

  if ($court_id) {
    // Map court code with ACCMS dist id.
    switch ($court_id) {
      case 'S':
        $accms_dist_id = '0';
        break;

      case 'A':
        $accms_dist_id = '1';
        break;

      case 'B':
        $accms_dist_id = '2';
        break;

      case 'C':
        $accms_dist_id = '3';
        break;

      case 'D':
        $accms_dist_id = '41';
        break;

      case 'E':
        $accms_dist_id = '42';
        break;

      case 'F':
        $accms_dist_id = '43';
        break;

      case 'G':
        $accms_dist_id = '5';
        break;

      case 'H':
        $accms_dist_id = '6';
        break;
    }

    // Assemble ACCMS lookup URL.
    $accms_url = '';
    $accms_dist_query = 'https://appellatecases.courtinfo.ca.gov/search/searchResults.cfm?dist=';
    if ($accms_dist_id) {
      $accms_url = $accms_dist_query . $accms_dist_id . '&search=number&useSession=0&query_caseNumber=' . $case_number;
    }
    elseif ($court_id == 'S') {
      $accms_url = $accms_dist_query . '0&search=number&useSession=0&query_caseNumber=' . $case_number;
    }
  }

  return $accms_url;
}

/**
 * Helper function to create links to remote opinion files.
 */
function __jcc_opinion_file_links_builder($type, $review_granted, $client, $file_type, $aria, $case) {
  // Current URL patterns.
  $base = 'https://courts.ca.gov/opinions/';
  $pub_path = 'documents/';
  $pub_ext_path = 'archive/';
  $pub_rev_path = 'revpub/';
  $non_pub_path = 'nonpub/';
  $non_pub_rev_path = 'revnppub/';

  // Set file path based on opinion type.
  switch ($type) {
    case 'Published - Extended Post':
      $path = $pub_ext_path;
      break;

    case 'Unpublished':
      $path = $non_pub_path;
      if ($review_granted) {
        $path = $non_pub_rev_path;
      }
      break;

    default:
      $path = $pub_path;
      if ($review_granted) {
        $path = $pub_rev_path;
      }
  }

  // Check if remote file exists.
  $url = $base . $path . $case . '.' . $file_type;
  $aria_label = $aria . ' ' . $case;
  try {
    $client->head($url)->getStatusCode();
    $response = $client->head($url)->getStatusCode();
  }
  catch (RequestException $e) {
    // If remote file for longer/extended post not found, try '/documents' path.
    if ($type == 'Published - Extended Post') {
      $url = $base . $pub_path . $case . '.' . $file_type;
      try {
        $client->head($url)->getStatusCode();
        $response = $client->head($url)->getStatusCode();
      }
      catch (RequestException $e) {
        $message = $e->getMessage();
        \Drupal::logger('opinion')->warning($message);
      }
    }
    else {
      $message = $e->getMessage();
      \Drupal::logger('opinion')->warning($message);
    }
  }

  // If remote file exists, setup data structure for component links.
  $link = FALSE;
  if ($response == 200) {
    $link = [
      'url' => $url,
      'file_type' => $file_type,
      'aria_label' => $aria_label,
    ];
  }

  return $link;
}
