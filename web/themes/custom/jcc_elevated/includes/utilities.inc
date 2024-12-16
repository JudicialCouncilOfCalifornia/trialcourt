<?php

/**
 * @file
 * Functions for views processing.
 */

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Render\Markup;
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

  if (isset($date['start_date'])) {
    [$start_day, $start_time] = explode($separator_item, $date['start_date']['#text']);
  }

  if (isset($date['end_date'])) {
    [$end_day, $end_time] = explode($separator_item, $date['end_date']['#text']);
  }

  if (isset($date['#text'])) {
    [$start_day, $start_time] = explode($separator_item, $date['#text']);
    [$end_day, $end_time] = explode($separator_item, $date['#text']);
  }

  $same_day = $start_day == $end_day;
  $same_time = $start_time == $end_time;
  $all_day = $start_time == "12:00 AM" && $end_time == "11:59 PM";
  $separate = FALSE;

  if ($same_day && !$all_day) {
    $date['start_date']['#text'] = $start_day;
    if ($same_time) {
      $date['end_date']['#text'] = '<small>' . $start_time . '</small>';
    }
    else {
      $date['end_date']['#text'] = '<small>' . $start_time . ' - ' . $end_time . '</small>';
    }
  }
  elseif ($same_day && $all_day) {
    $date['start_date']['#text'] = $start_day;
    $date['end_date']['#text'] = FALSE;
  }
  elseif (!$same_day && $all_day) {
    $separate = TRUE;
    $date['start_date']['#text'] = $start_day;
    $date['end_date']['#text'] = $end_day;
    $date['separator']['#plain_text'] = ' to ';
  }
  elseif (!$same_day && !$all_day) {
    $separate = TRUE;
    $date['start_date']['#text'] = $start_day . '<br><small>' . $start_time . '</small>';
    $date['end_date']['#text'] = $end_day . '<br><small>' . $end_time . '</small>';
    $date['separator']['#plain_text'] = ' to ';
  }
  else {
    $date['start_date']['#text'] = $start_day;
    if ($same_time) {
      $date['end_date']['#text'] = '<small>' . $start_time . '</small>';
    }
    else {
      $date['end_date']['#text'] = '<small>' . $start_time . ' - ' . $end_time . '</small>';
    }
  }

  $separator = $separate ? '<br><small>' . $date['separator']['#plain_text'] . '</small><br>' : '<br>';
  $start_datetime_attr = $date['start_date']['#attributes']['datetime'] ?? '';
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
        // Supreme Court.
        $accms_dist_id = '0';
        break;

      case 'A':
        // 1st District Court of Appeal.
        $accms_dist_id = '1';
        break;

      case 'B':
        // 2nd District Court of Appeal.
        $accms_dist_id = '2';
        break;

      case 'C':
        // Third District Court of Appeal.
        $accms_dist_id = '3';
        break;

      case 'D':
        // Fourth District Court of Appeal, Division 1.
        $accms_dist_id = '41';
        break;

      case 'E':
        // Fourth District Court of Appeal, Division 2.
        $accms_dist_id = '42';
        break;

      case 'G':
        // Fourth District Court of Appeal, Division 3.
        $accms_dist_id = '43';
        break;

      case 'F':
        // Fifth District Court of Appeal.
        $accms_dist_id = '5';
        break;

      case 'H':
        // Sixth District Court of Appeal.
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
function __jcc_opinion_file_links_builder($type, $review_granted, $file_type, $case) {
  // Current URL patterns.
  $base = 'https://www.courts.ca.gov/opinions/';
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

  // File URL.
  $url = $base . $path . $case . '.' . $file_type;

  // Confirm if DOCX vs DOC.
  $doc_response = NULL;
  if ($file_type == 'DOCX') {
    $client = \Drupal::httpClient();
    try {
      $doc_response = $client->head($url)->getStatusCode();
    }
    catch (RequestException $e) {
      // If DOCX not found, try DOC.
      $file_type = 'DOC';
      $url = $base . $path . $case . '.' . $file_type;
      try {
        $doc_response = $client->head($url)->getStatusCode();
      }
      catch (RequestException $e) {
        // If extended posts not found under /archive try /documents.
        if ($type == 'Published - Extended Post') {
          $file_type = 'DOCX';
          $url = $base . $pub_path . $case . '.' . $file_type;
          try {
            $doc_response = $client->head($url)->getStatusCode();
          }
          catch (RequestException $e) {
            $file_type = 'DOC';
            $url = $base . $pub_path . $case . '.' . $file_type;
            try {
              $doc_response = $client->head($url)->getStatusCode();
            }
            catch (RequestException $e) {
              \Drupal::logger('opinion')->warning($e->getMessage());
            }
          }
        }
      }
    }
  }

  // Build link.
  $link = NULL;
  if ($file_type == 'PDF' || $doc_response == 200) {
    $file_context = ['@file_type' => $file_type, '@case' => $case];
    $link = [
      'url' => $url,
      'file_type' => $file_type,
      'aria_label' => t('Download or view @file_type for case @case opinion', $file_context),
    ];
  }

  return $link;
}
