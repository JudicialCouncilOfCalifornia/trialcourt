**
 * Implements hook_form_FORM_ID_alter().
 */
function mymodule_form_node_location_edit_form_alter(&$form, &$form_state, $form_id) {
    $form['#validate'][] = 'mymodule_custom_validation';  // Add custom validation handler
}

/**
 * Custom validation handler.
 */
function mymodule_custom_validation($form, &$form_state) {
    $values = $form_state->getValues();
    $business_same_checked = $values['field_add_services'][0]['subform']['field_location_service_hours'][0]['subform']['field_service_hours_same_as_busi']['value'];

    if ($business_same_checked == 0) {  // Check if 'field_service_hours_same_as_busi' is unchecked
        // Check if 'from' and 'to' fields are filled
        if (empty($values['field_add_services'][0]['subform']['field_location_service_hours'][0]['subform']['field_service_hours_from'])) {
            form_set_error('field_add_services][0][subform][field_location_service_hours][0][subform][field_service_hours_from', t('Start time is required.'));
        }
        if (empty($values['field_add_services'][0]['subform']['field_location_service_hours'][0]['subform']['field_service_hours_to'])) {
            form_set_error('field_add_services][0][subform][field_location_service_hours][0][subform][field_service_hours_to', t('End time is required.'));
        }
    }
}
function mymodule_custom_validation(&$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $business_same_checked = $values['field_add_services'][0]['subform']['field_location_service_hours'][0]['subform']['field_service_hours_same_as_busi']['value'];

    if ($business_same_checked == 0) {  // Check if 'field_service_hours_same_as_busi' is unchecked
        // Validate 'from' time
        if (empty($values['field_add_services'][0]['subform']['field_location_service_hours'][0]['subform']['field_service_hours_from'])) {
            $form_state->setErrorByName('field_add_services][0][subform][field_location_service_hours][0][subform][field_service_hours_from', t('Start time is required.'));
        }
        // Validate 'to' time
        if (empty($values['field_add_services'][0]['subform']['field_location_service_hours'][0]['subform']['field_service_hours_to'])) {
            $form_state->setErrorByName('field_add_services][0][subform][field_location_service_hours][0][subform][field_service_hours_to', t('End time is required.'));
        }
    }
}



(function ($, Drupal) {
    Drupal.behaviors.focusFirstError = {
      attach: function (context, settings) {
        $(context).find('.form-item--error-message').first().prev('input, select, textarea').focus();
      }
    };
  })(jQuery, Drupal);
  
  function mymodule_custom_validation(&$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $services_path = 'field_add_services][0][subform][field_location_service_hours][0][subform]';
    $business_same_checked = $values['field_add_services'][0]['subform']['field_location_service_hours'][0]['subform']['field_service_hours_same_as_busi']['value'];

    // Proceed only if 'field_service_hours_same_as_busi' is unchecked
    if ($business_same_checked == 0) {
        // Fields to check if they are empty when business_same_checked is false
        $time_fields = ['field_service_hours_from', 'field_service_hours_to'];
        foreach ($time_fields as $field_name) {
            if (empty($values['field_add_services'][0]['subform']['field_location_service_hours'][0]['subform'][$field_name])) {
                $form_state->setErrorByName($services_path . "][$field_name", t('@field is required.', ['@field' => ucfirst(str_replace('_', ' ', $field_name))]));
            }
        }
    }
}




my logic
 if business_same_checked then print building Hours
 else print  service hours

 <?php
function getServiceHours($paragraph, $variables) {
    $all_hours = []; // Array to hold all hours details
    $previous_hour_type = ''; // To track the last processed hour type for detecting repetitions

    if ($paragraph->hasField('field_location_service_hours')) {
        foreach ($paragraph->get('field_location_service_hours')->referencedEntities() as $hourEntity) {
            $is_repeated = ($hourEntity->get('field_service_hour_type')->value === $previous_hour_type);
            $hour_details = [
                'mon_fri' => $hourEntity->get('field_weekdays_mon_fri')->value,
                'type' => $hourEntity->get('field_service_hour_type')->value,
                'is_repeated' => $is_repeated
            ];

            if (isset($hourEntity->get('field_service_hours_same_as_busi')->value) && $hourEntity->get('field_service_hours_same_as_busi')->value) {
                if ($hourEntity->get('field_weekdays_mon_fri')->value) {
                    $hour_details += [
                        'days' => "Monday - Friday",
                        'from' => $variables['building_hours']['building_starting_hours'],
                        'to' => $variables['building_hours']['building_closing_hours']
                    ];
                }
            } else {
                $days = [];
                if ($hourEntity->hasField('field_service_days') && !$hourEntity->get('field_weekdays_mon_fri')->value) {
                    foreach ($hourEntity->get('field_service_days') as $day) {
                        $days[] = Html::escape(ucwords($day->value));
                    }
                }
                $day_range = dayRange($days); // Function to create a string from the days array

                $hour_details += [
                    'days' => $day_range,
                    'from' => $hourEntity->hasField('field_service_hours_from') ? getFormattedDate($hourEntity->get('field_service_hours_from')->value) : '',
                    'to' => $hourEntity->hasField('field_service_hours_to') ? getFormattedDate($hourEntity->get('field_service_hours_to')->value) : ''
                ];
            }

            $all_hours[] = $hour_details;
            $previous_hour_type = $hourEntity->get('field_service_hour_type')->value; // Update the last processed hour type
        }
    }
    return $all_hours;
}


<?php
function getServiceHours($paragraph, $variables) {
    $all_hours = []; // Array to hold all hours details
    $previous_hour_type = ''; // To track the last processed hour type for detecting repetitions

    if ($paragraph->hasField('field_location_service_hours')) {
        foreach ($paragraph->get('field_location_service_hours')->referencedEntities() as $hourEntity) {
            $is_repeated = ($hourEntity->get('field_service_hour_type')->value === $previous_hour_type);

            if (isset($hourEntity->get('field_service_hours_same_as_busi')->value) && $hourEntity->get('field_service_hours_same_as_busi')->value == 1) {
                // Business hours are the same as building hours
                $hour_details = [
                    'days' => "Monday - Friday",
                    'from' => $variables['building_hours']['building_starting_hours'],
                    'to' => $variables['building_hours']['building_closing_hours'],
                    'type' => $hourEntity->get('field_service_hour_type')->value,
                    'is_repeated' => $is_repeated,
                ];
            } else {
                // Specific service hours
                $days = [];
                if ($hourEntity->hasField('field_service_days')) {
                    foreach ($hourEntity->get('field_service_days') as $day) {
                        $days[] = Html::escape(ucwords($day->value));
                    }
                }
                $day_range = dayRange($days); // Convert day array to a range string

                $hour_details = [
                    'days' => $day_range,
                    'from' => $hourEntity->hasField('field_service_hours_from') ? getFormattedDate($hourEntity->get('field_service_hours_from')->value) : '',
                    'to' => $hourEntity->hasField('field_service_hours_to') ? getFormattedDate($hourEntity->get('field_service_hours_to')->value) : '',
                    'type' => $hourEntity->get('field_service_hour_type')->value,
                    'is_repeated' => $is_repeated,
                ];
            }

            $all_hours[] = $hour_details;
            $previous_hour_type = $hourEntity->get('field_service_hour_type')->value; // Update the last processed hour type
        }
    }
    return $all_hours;
}

/**
 * Helper function to convert date values
 */
function getFormattedDate($date) {
    // Assume $date is a timestamp or formatted date string
    return date('g:i A', strtotime($date));
}

/**
 * Function to construct a day range from an array of days
 */
function dayRange($days) {
    if (empty($days)) {
        return '';
    }
    // Join all days with a comma and return
    return implode(', ', $days);
}
<?php
function getServiceHours($paragraph, $variables) {
    $all_hours = []; // Array to hold all hours details
    $previous_hour_type = ''; // To track the last processed hour type for detecting repetitions

    if ($paragraph->hasField('field_location_service_hours')) {
        foreach ($paragraph->get('field_location_service_hours')->referencedEntities() as $hourEntity) {
            $is_repeated = ($hourEntity->get('field_service_hour_type')->value === $previous_hour_type);

            if (isset($hourEntity->get('field_service_hours_same_as_busi')->value) && $hourEntity->get('field_service_hours_same_as_busi')->value == 1) {
                // Business hours are the same as building hours
                $hour_details = [
                    'days' => "Monday - Friday",
                    'from' => $variables['building_hours']['building_starting_hours'],
                    'to' => $variables['building_hours']['building_closing_hours'],
                    'type' => $hourEntity->get('field_service_hour_type')->value,
                    'is_repeated' => $is_repeated,
                ];
            } else {
                // Specific service hours
                $days = [];
                if ($hourEntity->hasField('field_service_days')) {
                    foreach ($hourEntity->get('field_service_days') as $day) {
                        $days[] = Html::escape(ucwords($day->value));
                    }
                }
                $day_range = dayRange($days); // Convert day array to a range string

                $hour_details = [
                    'days' => $day_range,
                    'from' => $hourEntity->hasField('field_service_hours_from') ? getFormattedDate($hourEntity->get('field_service_hours_from')->value) : '',
                    'to' => $hourEntity->hasField('field_service_hours_to') ? getFormattedDate($hourEntity->get('field_service_hours_to')->value) : '',
                    'type' => $hourEntity->get('field_service_hour_type')->value,
                    'is_repeated' => $is_repeated,
                ];
            }

            $all_hours[] = $hour_details;
            $previous_hour_type = $hourEntity->get('field_service_hour_type')->value; // Update the last processed hour type
        }
    }
    return $all_hours;
}

/**
 * Helper function to convert date values
 */
function getFormattedDate($date) {
    // Assume $date is a timestamp or formatted date string
    return date('g:i A', strtotime($date));
}

/**
 * Function to construct a day range from an array of days
 */
function dayRange($days) {
    if (empty($days)) {
        return '';
    }
    // Join all days with a comma and return
    return implode(', ', $days);
}

<?php
function getServiceHours($paragraph, $variables) {
    $all_hours = []; // Array to hold all hours details
    $previous_hour_type = ''; // To track the last processed hour type for detecting repetitions

    if ($paragraph->hasField('field_location_service_hours')) {
        foreach ($paragraph->get('field_location_service_hours')->referencedEntities() as $hourEntity) {
            $is_repeated = ($hourEntity->get('field_service_hour_type')->value === $previous_hour_type);

            if (isset($hourEntity->get('field_service_hours_same_as_busi')->value) && $hourEntity->get('field_service_hours_same_as_busi')->value == 1) {
                // Business hours are the same as building hours
                $hour_details = [
                    'days' => "Monday - Friday",
                    'from' => $variables['building_hours']['building_starting_hours'],
                    'to' => $variables['building_hours']['building_closing_hours'],
                    'type' => $hourEntity->get('field_service_hour_type')->value,
                    'is_repeated' => $is_repeated,
                ];
            } else {
                // Specific service hours or Monday - Friday option
                if ($hourEntity->get('field_weekdays_mon_fri')->value == 1) {
                    // Handle the case when Monday to Friday is checked but not same as building hours
                    $hour_details = [
                        'days' => "Monday - Friday",
                        'from' => $hourEntity->hasField('field_service_hours_from') ? getFormattedDate($hourEntity->get('field_service_hours_from')->value) : '',
                        'to' => $hourEntity->hasField('field_service_hours_to') ? getFormattedDate($hourEntity->get('field_service_hours_to')->value) : '',
                        'type' => $hourEntity->get('field_service_hour_type')->value,
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

/**
 * Helper function to convert date values
 */
function getFormattedDate($date) {
    // Assume $date is a timestamp or formatted date string
    return date('g:i A', strtotime($date));
}

/**
 * Function to construct a day range from an array of days
 */
function dayRange($days) {
    if (empty($days)) {
        return '';
    }
    // Join all days with a comma and return
    return implode(', ', $days);
}
Service Hours Same as Building Hours:

Condition Checked: The function looks for a boolean value in field_service_hours_same_as_busi. If this is 1, it indicates that the service hours are identical to the building's standard operational hours.
Action Taken: It sets the service hours to "Monday - Friday" with the predefined building start and end times. This simplifies scenarios where multiple services or departments follow the same operational schedule as the main building.
Standard Weekday Service Hours (Not Same as Building):

Condition Checked: If field_service_hours_same_as_busi is not 1, the function then checks field_weekdays_mon_fri.
When True: If field_weekdays_mon_fri is 1, it means the service operates during standard weekdays, but the hours differ from the building's hours.
Action Taken: The function fetches the specific 'from' and 'to' times from the service hour entity itself, setting these hours for Monday to Friday. This caters to services that operate on a regular weekday schedule but start earlier or close later than the main building.
Custom Service Days:

Condition Checked: If both previous conditions (field_service_hours_same_as_busi as 1 and field_weekdays_mon_fri as 1) are not met, the function assumes there are specific service days provided.
Action Taken:
Days Collection: It collects days from field_service_days, processes each day to capitalize and make them HTML-safe via Html::escape and ucwords.
Day Range String: Constructs a string representing the range of these days using a helper function dayRange. This could be a comma-separated list of days like "Monday, Wednesday, Friday".
Fetching Specific Times: Retrieves specific 'from' and 'to' times for these days from the service hours entity.
Outcome: This configuration is suited for services that might operate only on certain days of the week or have varying hours on different days, providing a highly customized schedule.
Repetition Detection: Each loop iteration checks if the current hour type is the same as the previous. This information (is_repeated) could be used for display purposes to group similar service hour types or avoid redundancy in listings.
Comprehensive Output: Each set of conditions results in a detailed dictionary (hour_details) containing all relevant info for a set of service hours, which is then added to an overall list (all_hours). This list is returned at the end, providing a structured output of all service hour configurations for further use in the application.