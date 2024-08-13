<?php

/**
 * @file
 * Preprocess and functions for alert content type and component.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\media\Entity\Media;
use Drupal\node\NodeInterface;
use Drupal\Core\Render\Markup;
use Drupal\Component\Utility\Html;

/**
 * helper function location page template variable.
 */
function buildServiceMarkup(&$variables) {
  $node = $variables['node'];
  if ($node->getType() !== 'location') {
      return;
  }

  $variables['#attached']['library'][] = 'jcc_components/location-page';

  $variables['public_transportation'] = [
      'parking' => getFieldLink($node, 'field_parking_link'),
      'transportation' => getFieldLink($node, 'field_public_transportation_link'),
  ];

  if ($node->hasField('field_building_hours') && $node->hasField('field_building_hours_to')) {
    $variables['building_hours'] = [
          'building_starting_hours' => (new DateTime($node->get('field_building_hours')->value))->format('g:ia'),
          'building_closing_hours' => (new DateTime($node->get('field_building_hours_to')->value))->format('g:ia'),
      ];
  }

  if ($node->hasField('field_cases_served')) {
      $variables['case_category_terms'] = array_map(function($term_reference) {
          return Html::escape($term_reference->getName());
      }, $node->get('field_cases_served')->referencedEntities());
  }
  processAddServices($node, $variables);
}

function getFieldLink($node, $fieldName) {
  if ($node->hasField($fieldName) && !$node->get($fieldName)->isEmpty()) {
      $fieldValue = $node->get($fieldName)->first()->getValue();
      return [
          'url' => $fieldValue['uri'],
          'title' => $fieldValue['title'],
      ];
  }
  return [];
}

function processAddServices($node, &$variables) {
  if (!$node->hasField('field_add_services')) {
      return;
  }

  $add_services = [];
  foreach ($node->get('field_add_services') as $paragraph_reference) {
      if ($paragraph_reference->entity) {
          $paragraph = $paragraph_reference->entity;
          $services = extractServiceDetails($paragraph);
          $services['hours'] = getServiceHours($paragraph);
          $add_services[] = createServiceCard($services ,$variables);
      }
    //  kint($service);
     // kint($service['hours']);
  }

  $variables['add_services'] = [
      'items' => $add_services,
      'num_cols' => 1
  ];
}

function extractServiceDetails($paragraph) {
  $services = [
      'name' => $paragraph->get('field_service_name')->value,
      'phone_number' => $paragraph->hasField('field_service_phone_number') ? Html::escape($paragraph->get('field_service_phone_number')->value) : '',
      'email' => $paragraph->hasField('field_service_email_address') ? Html::escape($paragraph->get('field_service_email_address')->value) : '',
      'fax' => $paragraph->hasField('field_service_fax_number') ? Html::escape($paragraph->get('field_service_fax_number')->value) : '',
      'additional_info' => $paragraph->hasField('field_additional_information') ? Html::escape($paragraph->get('field_additional_information')->value) : '',
      'categories' => $paragraph->hasField('field_service_category') ? array_map(function($term_reference) {
          return Html::escape($term_reference->getName());
      }, $paragraph->get('field_service_category')->referencedEntities()) : []
  ];
  return $services;
}

/**
 * Extracts and formats service hours from a paragraph entity.
 */
function getServiceHours($paragraph) {
    $all_hours = []; // Array to hold all hours details
    $previous_hour_type = ''; // To track the last processed hour type for detecting repetitions

    if ($paragraph->hasField('field_location_service_hours')) {
        foreach ($paragraph->get('field_location_service_hours')->referencedEntities() as $hourEntity) {
            $days = [];
            if ($hourEntity->hasField('field_service_days')) {
                // Collect all days and capitalize them
                foreach ($hourEntity->get('field_service_days') as $day) {
                    $days[] = Html::escape(ucwords($day->value));
                }
            }

            // Day range is used to create a string from the days array
            $day_range = dayRange($days);

            // Extract the time details
            $from_time = $hourEntity->hasField('field_service_hours_from') ?
                (new DateTime($hourEntity->get('field_service_hours_from')->value))->format('g:ia') : '';
            $to_time = $hourEntity->hasField('field_service_hours_to') ?
                (new DateTime($hourEntity->get('field_service_hours_to')->value))->format('g:ia') : '';

            // Determine if the current hour type has been repeated
            $is_repeated = ($hourEntity->get('field_service_hour_type')->value === $previous_hour_type);

            // Assemble details of current service hour
            $hour_details = [
                'days' => $day_range,
                'from' => $from_time,
                'to' => $to_time,
                'mon_fri' => $hourEntity->get('field_weekdays_mon_fri')->value,
                'type' => $hourEntity->get('field_service_hour_type')->value,
                'is_repeated' => $is_repeated
            ];

            // Update the last processed hour type
            $previous_hour_type = $hourEntity->get('field_service_hour_type')->value;

            // Add the current hour details to the list of all hours
            $all_hours[] = $hour_details;
        }
    }
    
    return $all_hours;
}

function createServiceCard($services,$variables) {
  $body_content = buildBodyContent($services ,$variables);
  return [
      'style' => 'borderless',
      'title' => Markup::create($services['name']),
      'body' => Markup::create($body_content),
      'link' => [
          'url' => '#',
          'text' => 'Learn more',
          'type' => 'internal'
      ]
  ];
}

function buildBodyContent($services ,$variables) {
  $content = '';
  $phone_number_displayed = false; // Flag to track if the phone number has already been displayed
  $building_hours_displayed = false; // Flag to track if building hours have already been displayed

  
    $content .= "<b>Hours: </b>" .  $variables['building_hours']['building_starting_hours'] . " - " . $variables['building_hours']['building_closing_hours'] . "<br />";
    $content .= "<b>Phone Number: </b>" .  $services['phone_number'] . "<br />";
    $building_hours_displayed = true;
  
    $previous_hour_type = "";
    $content .= '<div class = "hours">';
  foreach ($services['hours'] as $key => $hour) {
     // Process only if it's a new type of hours or phone number
      // Check if service hours correspond to Monday-Friday and if they haven't been displayed yet
    if ( $hour['mon_fri'] != 1 && $hour['is_repeated'] == false && $previous_hour_type == "") {
        $content .= '<div class= "hour-row">';
        $content .= '<span class ="hour-label"><b>Hours:</b> ' . ucwords(str_replace('_', ' ', $hour['type'])) . ": ". '</span>';
        $content .= '<span class ="hour-value">'.$hour['days'] . $hour['from'] . " - " . $hour['to'] . "<br />".'</span>';
        $content .= '</div>';
        $previous_hour_type =$hour['type'];
    } 

    if ( $hour['mon_fri'] != 1 && $hour['is_repeated'] == false && $previous_hour_type != $hour['type']) {
      $content .= '<div class= "hour-row new-type">';
      $content .= '<span class ="new-type">';
      $content .=  ucwords(str_replace('_', ' ', $hour['type'])) . ": " ;
      $content .= $hour['days'] . $hour['from'] . " - " . $hour['to'] . "<br />";
      $content .= '</span>';
      $content .= '</div>';
      $previous_hour_type =$hour['type'];
     }

    if ( $hour['mon_fri'] != 1 && $hour['is_repeated'] == true) {
      $content .= '<div class= "hour-row repeated">';
      $content .= '<span class ="hour-value">'.$hour['days'] . $hour['from'] . " - " . $hour['to'] . "<br />". '</span>';
      $content .= '</div>';
  } 

// make division 3 column for service hors type hors

  }
  $content .= '</div>';
   // Always display additional information at the end
   //$content .= "<b>Additional Info:</b> " . $services['additional_info'] . "<br />";
   return $content;
}
 


function dayRange($days){
// Example array of selected days, as retrieved from the field.
$selected_days =$days; // This can be dynamic.

// Define the order of the days.
$day_order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

// Sort the selected days according to the defined order.
usort($selected_days, function($a, $b) use ($day_order) {
  return array_search($a, $day_order) - array_search($b, $day_order);
});

// Group consecutive days.
$grouped_days = [];
$group_start = $selected_days[0];
$prev_day = array_search($group_start, $day_order);

foreach ($selected_days as $i => $day) {
  $current_day = array_search($day, $day_order);
  if ($current_day !== $prev_day + 1 && $i !== 0) {
    $grouped_days[] = $group_start . ($group_start !== $selected_days[$i - 1] ? " - " . $selected_days[$i - 1] : "");
    $group_start = $day;
  }
  $prev_day = $current_day;
}

// Add the last group.
$grouped_days[] = $group_start . ($group_start !== end($selected_days) ? " - " . end($selected_days) : "");

// Join the groups into a final string.
$formatted_days = implode(', ', $grouped_days);

// Output the result.
return $formatted_days; // Outputs: "Monday - Wednesday"
}