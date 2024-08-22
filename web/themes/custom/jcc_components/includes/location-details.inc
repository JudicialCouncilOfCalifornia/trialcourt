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
function buildServiceMarkup(&$variables ,$node) {
  $variables['public_transportation'] = [
      'parking' => getFieldLink($node, 'field_parking_link'),
      'transportation' => getFieldLink($node, 'field_public_transportation_link'),
  ];

  if ($node->hasField('field_building_hours_from') && $node->hasField('field_building_hours_to')) {
    $default_timezone =  \Drupal::config('system.date')->get('timezone.default');
    $date = (new DateTime($node->get('field_building_hours_from')->value,new \DateTimeZone('UTC')));
    $date->setTimeZone(new \DateTimeZone($default_timezone));
   
    $building_hours_from = getFormattedDate($node->get('field_building_hours_from')->value);
    $building_hours_to = getFormattedDate($node->get('field_building_hours_to')->value);
    $variables['building_hours'] = [
          'building_starting_hours' => $building_hours_from ,
          'building_closing_hours' => $building_hours_to ,
      ];
  }

  if ($node->hasField('field_cases_served')) {
      $variables['case_category_terms'] = array_map(function($term_reference) {
          return $term_reference->getName();
      },$node->get('field_cases_served')->referencedEntities());
      //kint($variables['case_category_terms']) ;
  }
  processAddServices($node, $variables);
}

/**
 * Helper function for rendering time in site default time zone.
 */
function getFormattedDate($raw_time) {
      $default_timezone =  \Drupal::config('system.date')->get('timezone.default');
      $date = new \DateTime($raw_time, new \DateTimeZone('UTC'));
      $date->setTimezone(new \DateTimeZone($default_timezone));  // Adjust timezone to match site's configuration
      $formatted_time = $date->format('g:ia');
      $formatted_time = str_replace(['am','pm'],[' a.m.' , ' p.m.'] ,$formatted_time );
      return $formatted_time;  // Adjust format as needed
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

/**
 * Add Service variable
 * */
function processAddServices($node, &$variables) {
  if (!$node->hasField('field_add_services')) {
      return;
  }

  $add_services = [];
  foreach ($node->get('field_add_services') as $paragraph_reference) {
      if ($paragraph_reference->entity) {
          $paragraph = $paragraph_reference->entity;
          $services = extractServiceDetails($paragraph);
          $services['hours'] = getServiceHours($paragraph ,$variables);
          $add_services[] = createServiceCard($services ,$variables);
      }
  }
  //kint($services);

  $variables['add_services'] = [
      'items' => $add_services,
      'num_cols' => 1
  ];
}

function extractServiceDetails($paragraph) {
  $services = [
      'name' => ucfirst($paragraph->get('field_service_name')->value),
      'phone_number' => $paragraph->hasField('field_service_phone_number') ? Html::escape($paragraph->get('field_service_phone_number')->value) : '',
      'email' => $paragraph->hasField('field_service_email_address') ? Html::escape($paragraph->get('field_service_email_address')->value) : '',
      'fax' => $paragraph->hasField('field_service_fax_number') ? Html::escape($paragraph->get('field_service_fax_number')->value) : '',
      'additional_info' => $paragraph->hasField('field_additional_information') ? Html::escape($paragraph->get('field_additional_information')->value) : '',
      'service_appointment' =>  $paragraph->hasField('field_additional_information') ? createLink($paragraph->get('field_service_appointment')->getValue()) : '',
      'categories' => $paragraph->hasField('field_service_category') ? array_map(function($term_reference) {
          return Html::escape($term_reference->getName());
      }, $paragraph->get('field_service_category')->referencedEntities()) : []
  ];
  return $services;
}

/**
 * Create link from url value
 */
function createLink($appointment_link){
  if(isset($appointment_link['url'] ) && isset($appointment_link['url'] )){
    $appointment_link['url']    =  $appointment_link['0']['uri'];
    $appointment_link['title']  = $appointment_link['0']['title'] ? $appointment_link['0']['title'] : 'Schedule an Appointment';
    return '<a href ="'. $appointment_link['title'].'">'. $appointment_link['title'] .'</a>';
  }
}

/**
 * Extracts and formats service hours from a paragraph entity.
 */
function getServiceHours($paragraph , $variables) {
  $all_hours = []; // Array to hold all hours details
  $previous_hour_type = ''; // To track the last processed hour type for detecting repetitions

  if ($paragraph->hasField('field_location_service_hours')) {
      foreach ($paragraph->get('field_location_service_hours')->referencedEntities() as $hourEntity) {
          $is_repeated = ($hourEntity->get('field_service_hour_type')->value === $previous_hour_type);
          $hour_type = $hourEntity->get('field_service_hour_type')->value !="others" ? $hourEntity->get('field_service_hour_type')->value:
          $hourEntity->get('field_others')->value;
          if (isset($hourEntity->get('field_service_hours_same_as_busi')->value) && $hourEntity->get('field_service_hours_same_as_busi')->value == 1) {
              // Business hours are the same as building hours
              $hour_details = [
                  'mon-fri' => 1,
                  'days' => "Monday - Friday",
                  'from' => $variables['building_hours']['building_starting_hours'],
                  'to' => $variables['building_hours']['building_closing_hours'],
                  'type' => $hour_type ,
                  'is_repeated' => $is_repeated,
              ];
          } else {
              // Specific service hours or Monday - Friday option
              if ($hourEntity->get('field_weekdays_mon_fri')->value == 1) {
                  // Handle the case when Monday to Friday is checked but not same as building hours
                  $hour_details = [
                      'mon-fri' => 1,
                      'days' => "Monday - Friday",
                      'from' => $hourEntity->hasField('field_service_hours_from') ? getFormattedDate($hourEntity->get('field_service_hours_from')->value) : '',
                      'to' => $hourEntity->hasField('field_service_hours_to') ? getFormattedDate($hourEntity->get('field_service_hours_to')->value) : '',
                      'type' => $hour_type,
                      'is_repeated' => $is_repeated,
                  ];
              } else {
                  // Specific service days not following the Monday to Friday schedule
                  $days = [];
                  if ($hourEntity->hasField('field_service_days')) {
                      foreach ($hourEntity->get('field_service_days') as $day) {
                          $days[] = Html::escape(ucwords($day->value));
                      }
                  }
                  $day_range = dayRange($days); // Convert day array to a range string

                  $hour_details = [
                      'mon-fri' => 0,
                      'days' => $day_range,
                      'from' => $hourEntity->hasField('field_service_hours_from') ? getFormattedDate($hourEntity->get('field_service_hours_from')->value) : '',
                      'to' => $hourEntity->hasField('field_service_hours_to') ? getFormattedDate($hourEntity->get('field_service_hours_to')->value) : '',
                      'type' => $hourEntity->get('field_service_hour_type')->value,
                      'is_repeated' => $is_repeated,
                  ];
              }
          }

          $all_hours[] = $hour_details;
          $previous_hour_type = $hourEntity->get('field_service_hour_type')->value; // Update the last processed hour type
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

function buildBodyContent($services, $variables) {
  $content = '';
  // Display static building hours and phone number information.
  $space_one = "&nbsp;";
  $content .= "<b>Phone Number: </b>" . $services['phone_number'] . "<br />";
  if(isset($services['email']) && $services['email'] != ""){
    $content .= '<b>Email</b>:'.$space_one. Markup::create($services['email']) . "<br />";
  }
  
  $previous_hour_type = "";
  $is_first_row = true; // Initialize the first row flag
  $content .= '<div class="hours">';
  $content.= '<div class="flex-item"><b>Service Hours:</b></div>';
  $appointment =  Markup::create($services['service_appointment']);
  foreach ($services['hours'] as $hour) {
      // Simplify hour row creation by using a helper function
      $content .= formatHourRow($hour, $previous_hour_type,$is_first_row ,$services['service_appointment']);
      $previous_hour_type = $hour['type'];
      $is_first_row = false;
  }

  $content .= '</div>';
  // Uncomment to always display additional information at the end
  // $content .= "<b>Additional Info:</b> " . $services['additional_info'] . "<br />";
  return $content;
}

// Helper function to format hour rows based on the type of hours and repetition status
function formatHourRow($hour, $previous_hour_type, $is_first_row ,$appointment) {
  $row_content = '';
  $type = '<div class="flex-item">' . ucwords(str_replace('_', ' ', $hour['type'])) . ":" . '</div>';
  $appointment_string = '<br />'.$appointment ;
  $time_span = '<div class="flex-item">' . $hour['days'] . "&nbsp" . $hour['from'] . " - " . $hour['to'].$appointment_string .'</div>';
  if ( $hour['mon-fri'] != 1 && $hour['is_repeated'] == false && $previous_hour_type != $hour['type']) {
      $appointment_col = ($hour['type'] == "phone")? $appointment : "";
      $row_content .= '<div class="hour-row">' . $type . $time_span .'</div>';
  } else if ($hour['mon-fri']!= 1 && $hour['is_repeated'] == true) {
      // Modified for when hours are repeated
      $appointment_col = ($hour['type'] == "phone")? $appointment : "";
      $row_content .= '<div class="hour-row">' . $type . $time_span . '</div>';
  } else if ($hour['mon-fri']== 1) {
      // Special formatting for Monday-Friday hours if treated differently
      $appointment_col = ($hour['type'] == "phone")? $appointment : "";
      $row_content .= '<div class="hour-row">' . $type . $time_span  . '</div>';
  }
  return $row_content;
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