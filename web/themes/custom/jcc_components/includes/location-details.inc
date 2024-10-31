<?php

/**
 * @file
 * Preprocess and functions for alert content type and component.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Markup;

/**
 * Helper function location page template variable.
 */
function build_service_markup(&$variables, $node) {
  $variables['public_transportation'] = [
    'parking' => get_field_link($node, 'field_parking_link'),
    'transportation' => get_field_link($node, 'field_public_transportation_link'),
  ];

  if ($node->hasField('field_building_hours_from') && $node->hasField('field_building_hours_to')) {
    $default_timezone = \Drupal::config('system.date')->get('timezone.default');
    $date = (new DateTime($node->get('field_building_hours_from')->value, new \DateTimeZone('UTC')));
    $date->setTimeZone(new \DateTimeZone($default_timezone));

    $building_hours_from = get_formatted_date($node->get('field_building_hours_from')->value);
    $building_hours_to = get_formatted_date($node->get('field_building_hours_to')->value);
    $variables['building_hours'] = [
      'building_starting_hours' => $building_hours_from,
      'building_closing_hours' => $building_hours_to ,
    ];
  }

  if ($node->hasField('field_cases_served')) {
    $variables['case_category_terms'] = array_map(function ($term_reference) {
        return $term_reference->getName();
    }, $node->get('field_cases_served')->referencedEntities());
  }

  // Check if the field_others field exists and has a value, and then explode it into an array.
  $additional_cases_served = [];
  if ($node->hasField('field_others') && !empty($node->get('field_others')->value)) {
      $additional_cases_served = explode(",", $node->get('field_others')->value);
  }

  // Ensure both case_category_terms and additional_cases_served are arrays before merging.
  $case_category_terms = isset($variables['case_category_terms']) && is_array($variables['case_category_terms']) 
      ? $variables['case_category_terms'] 
      : [];

  // Merge case_category_terms and additional_cases_served if both are arrays, otherwise use case_category_terms alone.
  $variables['case_served'] = array_values(array_merge($case_category_terms, $additional_cases_served));
  process_add_services($node, $variables);
}

/**
 * Helper function for rendering time in site default time zone.
 */
function get_formatted_date($raw_time) {
  $default_timezone = \Drupal::config('system.date')->get('timezone.default');
  $date = new \DateTime($raw_time, new \DateTimeZone('UTC'));
  // Adjust timezone to match site's configuration.
  $date->setTimezone(new \DateTimeZone($default_timezone));
  $formatted_time = $date->format('g:ia');
  $formatted_time = str_replace(['am', 'pm'], [' a.m.', ' p.m.'], $formatted_time);
  // Adjust format as needed.
  return $formatted_time;
}

/**
 * Helper function to get link from field name.
 */
function get_field_link($node, $fieldName) {
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
 * Add Service variable.
 * */
function process_add_services($node, &$variables) {
  if (!$node->hasField('field_add_services')) {
    return;
  }

  $add_services = [];
  foreach ($node->get('field_add_services') as $paragraph_reference) {
    if ($paragraph_reference->entity) {
      $paragraph = $paragraph_reference->entity;
      $services = extract_service_details($paragraph);
      $services['hours'] = get_service_hours($paragraph, $variables);
      $add_services[] = create_service_card($services, $variables);
    }
  }

  $variables['add_services'] = [
    'items' => $add_services,
    'num_cols' => 1,
  ];
}

/**
 * Getting the service details field values.
 */
function extract_service_details($paragraph) {
  // Check if service name has link associated to it.
  if ($paragraph->hasField('field_services_link') &&  isset($paragraph->get('field_services_link')->getValue()['0'])) {
    $service_link = $paragraph->get('field_services_link')->getValue()['0']['uri'];
    // Add check if string contains internal.
    $service_link = substr($service_link, strlen('internal:'));
  }

  if (isset($service_link)  && $service_link != "") {
    $service_name = '<a href ="' . htmlspecialchars($service_link) . '">' . ucfirst($paragraph->get('field_service_name')->value) . '</a>';
  }
  else {
    $service_name = '<div class = "no-link">' . ucfirst($paragraph->get('field_service_name')->value) . '</div>';
  }

  $services = [
    'name' => $service_name,
    'phone_number' => $paragraph->hasField('field_service_phone_number') ? Html::escape($paragraph->get('field_service_phone_number')->value) : '',
    'email' => $paragraph->hasField('field_service_email_address') ? Html::escape($paragraph->get('field_service_email_address')->value) : '',
    'fax' => $paragraph->hasField('field_service_fax_number') ? Html::escape($paragraph->get('field_service_fax_number')->value) : '',
    'additional_info' => $paragraph->hasField('field_additional_information') ? Html::escape($paragraph->get('field_additional_information')->value) : '',
    'service_appointment' => $paragraph->hasField('field_service_appointment') ? create_link($paragraph->get('field_service_appointment')->getValue()) : '',
    'categories' => $paragraph->hasField('field_service_category') ? array_map(function ($term_reference) {
          return Html::escape($term_reference->getName());
    }, $paragraph->get('field_service_category')->referencedEntities()) : [],
  ];
  return $services;
}

/**
 * Create link from url field.
 */
function create_link($appointment_link) {
  if (!empty($appointment_link)) {
    $appointment_link['url']   = $appointment_link['0']['uri'];
    $appointment_link['title'] = $appointment_link['0']['title'] != "" ? $appointment_link['0']['title'] : 'Schedule an Appointment';
    return '<a href ="' . $appointment_link['title'] . '">' . $appointment_link['title'] . '</a>';
  }
}

/**
 * Extracts and formats service hours from a paragraph entity.
 */
function get_service_hours($paragraph, $variables) {
  // Array to hold all hours details.
  $all_hours = [];
  // To track the last processed hour type for detecting repetitions.
  $previous_hour_type = '';

  if ($paragraph->hasField('field_location_service_hours')) {
    foreach ($paragraph->get('field_location_service_hours')->referencedEntities() as $hourEntity) {
      $is_repeated = ($hourEntity->get('field_service_hour_type')->value === $previous_hour_type);
      $hour_type = $hourEntity->get('field_service_hour_type')->value != "others" ? $hourEntity->get('field_service_hour_type')->value :
          $hourEntity->get('field_others')->value;
      $is_type_others = $hourEntity->get('field_service_hour_type')->value == "others" ? TRUE : FALSE;
      if ($is_type_others) {
        $others_field_value = $hour_type;
      }
      else {
        $others_field_value = NULL;
      }
      if (isset($hourEntity->get('field_service_hours_same_as_busi')->value) && $hourEntity->get('field_service_hours_same_as_busi')->value == 1) {
        // Business hours are the same as building hours.
        $hour_details = [
          'mon-fri' => 1,
          'days' => "Monday - Friday",
          'from' => $variables['building_hours']['building_starting_hours'],
          'to' => $variables['building_hours']['building_closing_hours'],
          'type' => $hour_type ,
          'is_repeated' => $is_repeated,
          'is_others' => $is_type_others,
          '$others_field_value' => $others_field_value,
        ];
      }
      else {
        // Specific service hours or Monday - Friday option.
        if ($hourEntity->get('field_weekdays_mon_fri')->value == 1) {
          // Handle the case when Monday to Friday is
          // checked but not same as building hours.
          $hour_details = [
            'mon-fri' => 1,
            'days' => "Monday - Friday",
            'from' => $hourEntity->hasField('field_service_hours_from') ? get_formatted_date($hourEntity->get('field_service_hours_from')->value) : '',
            'to' => $hourEntity->hasField('field_service_hours_to') ? get_formatted_date($hourEntity->get('field_service_hours_to')->value) : '',
            'type' => $hour_type,
            'is_repeated' => $is_repeated,
            'is_others' => $is_type_others,
            '$others_field_value' => $others_field_value,
          ];
        }
        else {
          // Specific service days not following the Monday to Friday schedule.
          $days = [];
          if ($hourEntity->hasField('field_service_days')) {
            foreach ($hourEntity->get('field_service_days') as $day) {
              $days[] = Html::escape(ucwords($day->value));
            }
          }
          // Convert day array to a range string.
          $day_range = day_range($days);

          $hour_details = [
            'mon-fri' => 0,
            'days' => $day_range,
            'from' => $hourEntity->hasField('field_service_hours_from') ? get_formatted_date($hourEntity->get('field_service_hours_from')->value) : '',
            'to' => $hourEntity->hasField('field_service_hours_to') ? get_formatted_date($hourEntity->get('field_service_hours_to')->value) : '',
            'type' => $hourEntity->get('field_service_hour_type')->value,
            'is_repeated' => $is_repeated,
            'is_others' => $is_type_others,
            '$others_field_value' => $others_field_value,
          ];
        }
      }

      $all_hours[] = $hour_details;
      // Update the last processed hour type.
      $previous_hour_type = $hourEntity->get('field_service_hour_type')->value;
    }
  }
  return $all_hours;
}

/**
 * Create service cards to be used in twig template.
 */
function create_service_card($services, $variables) {
  $body_content = build_body_content($services, $variables);
  return [
    'style' => 'borderless',
    'title' => Markup::create($services['name']),
    'body' => Markup::create($body_content),
  ];
}

/**
 * Build the content that will be associated with each service.
 */
function build_body_content($services, $variables) {
  $content = '';
  // Display static building hours and phone number information.
  if (isset($services['phone_number']) && $services['phone_number'] != "") {
    $content .= '<div class ="service-info">';
    $content .= '<div class="flex-item"><b>Phone:</b></div>' . '<div class="flex-item">' . '<a class ="jcc-location-service-phone-link" href="tel:' . $services['phone_number'] . '"' . ' class="mailto" data-extlink="">' . $services['phone_number'] . '</a>' . '</div>';
    $content .= '</div>';
  }

  if (isset($services['email']) && $services['email'] != "") {
    $content .= '<div class ="service-info">';
    $content .= '<div class="flex-item"><b> Email:</b></a></div>' . '<a href="mailto:' . $services['email'] . '"' . ' class="mailto" data-extlink="">' . $services['email'] . '</a>' . '</div>';
    $content .= '</div>';
  }

  if (isset($services['fax']) && $services['fax'] != "") {
    $content .= '<div class ="service-info">';
    $content .= '<div class="flex-item"><b>Fax:</b></div>' . '<div class="flex-item">' . $services['fax'] . '</div>';
    $content .= '</div>';
  }

  $previous_hour_type = "";
  // Initialize the first row flag.
  $is_first_row = TRUE;
  $content .= '<div class="hours">';
  $content .= '<div class="flex-item"><b>Service Hours:</b></div>';
  foreach ($services['hours'] as $hour) {
    // Simplify hour row creation by using a helper function.
    $content .= format_hour_row($hour, $previous_hour_type, $is_first_row, $services['service_appointment']);
    $previous_hour_type = $hour['type'];
    $is_first_row = FALSE;
  }

  $content .= '</div>';
  $content .= '<div class ="service-info">';
  if (isset($services['additional_info']) && $services['additional_info'] != "") {
    $content .= '<div class="flex-item additional_info">' . $services['additional_info'] . '</div>';
  }
  return $content;
}

/**
 * Format hour rows based on the type of hours and repetition status.
 */
function format_hour_row($hour, $previous_hour_type, $is_first_row, $appointment) {
  $row_content = '';
  $type = '<div class="flex-item">' . ucwords(str_replace('_', ' ', $hour['type'])) . ":" . '</div>';
  $allowed_value_appointment = ['in_person'];
  $allowed_value_appointment[] = $hour['$others_field_value'];
  $appointment_string = (in_array($hour['type'], $allowed_value_appointment) && $hour['is_repeated'] == FALSE) ? '<br />' . $appointment : '';
  $time_span = '<div class="flex-item">' . $hour['days'] . "&nbsp" . $hour['from'] . "-" . $hour['to'] . $appointment_string . '</div>';
  if ($hour['mon-fri'] != 1 && $hour['is_repeated'] == FALSE && $previous_hour_type != $hour['type']) {
    $row_content .= '<div class="hour-row">' . $type . $time_span . '</div>';
  }
  elseif ($hour['mon-fri'] != 1 && $hour['is_repeated'] == TRUE) {
    // Modified for when hours are repeated.
    $row_content .= '<div class="hour-row">' . $type . $time_span . '</div>';
  }
  elseif ($hour['mon-fri'] == 1) {
    // Special formatting for Monday-Friday hours if treated differently.
    $row_content .= '<div class="hour-row">' . $type . $time_span . '</div>';
  }
  return $row_content;
}

/**
 * Function to convert  consecutive day to day range.
 */
function day_range($days) {
  // Example array of selected days, as retrieved from the field.
  // This can be dynamic.
  $selected_days = $days;

  // Define the order of the days.
  $day_order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday',
    'Friday', 'Saturday', 'Sunday',
  ];

  // Sort the selected days according to the defined order.
  usort($selected_days, function ($a, $b) use ($day_order) {
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
  // Outputs: "Monday - Wednesday".
  return $formatted_days;
}
