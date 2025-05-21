<?php
function getServiceHours($paragraph , $variables) {
  $all_hours = []; // Array to hold all hours details
  $previous_hour_type = ''; // To track the last processed hour type for detecting repetitions
  if ($paragraph->hasField('field_location_service_hours')) {
    foreach ($paragraph->get('field_location_service_hours') as $service_hours_reference) {
      if ($service_hours_reference->entity) {
        $service_hours = $service_hours_reference->entity;
      }
    }

    foreach ($paragraph->get('field_location_service_hours')->referencedEntities() as $hourEntity) {
      $days = [];
      if (isset($hourEntity->get('field_service_hours_same_as_busi')->value) && $hourEntity->get('field_service_hours_same_as_busi')->value == 0){
        if ($hourEntity->hasField('field_service_days') && $hourEntity->get('field_weekdays_mon_fri')->value == 0) {
          // Collect all days and capitalize them
          foreach ($hourEntity->get('field_service_days') as $day) {
            $days[] = Html::escape(ucwords($day->value));
          }
          // Day range is used to create a string from the days array
          $day_range = dayRange($days);
        }
        // Extract the time details
        $from_time = $hourEntity->hasField('field_service_hours_from') ?
          getFormattedDate($hourEntity->get('field_service_hours_from')->value) : '';
        $to_time = $hourEntity->hasField('field_service_hours_to') ?
          getFormattedDate($hourEntity->get('field_service_hours_to')->value) : '';

        // Determine if the current hour type has been repeated
        $is_repeated = ($hourEntity->get('field_service_hour_type')->value === $previous_hour_type);

        // Determine if the current hour type has been repeated

        // Assemble details of current service hour
        $hour_details = [
          'days' => $day_range,
          'from' => $from_time,
          'to' => $to_time,
          'mon_fri' => $hourEntity->get('field_weekdays_mon_fri')->value,
          'type' => $hourEntity->get('field_service_hour_type')->value,
          'is_repeated' => $is_repeated,
        ];

        // Update the last processed hour type
        $previous_hour_type = $hourEntity->get('field_service_hour_type')->value;

      }
      elseif(isset($hourEntity->get('field_service_hours_same_as_busi')->value) && $hourEntity->get('field_service_hours_same_as_busi')->value == 1){
        if($hourEntity->get('field_weekdays_mon_fri')->value == 1){

          $hour_details = [
            'days' => "Monday - Friday",
            'from' => $variables['building_hours']['building_starting_hours'],
            'to' => $variables['building_hours']['building_closing_hours'],
            'mon_fri' => $hourEntity->get('field_weekdays_mon_fri')->value,
            'type' => $hourEntity->get('field_service_hour_type')->value,
            'is_repeated' => $is_repeated,
          ];
        }

      }
      // Add the current hour details to the list of all hours
      $all_hours[] = $hour_details;
    }
  }
  return $all_hours;
}
