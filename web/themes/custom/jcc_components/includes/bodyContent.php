<?php
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
    if ( $hour['mon_fri'] != 1 && $hour['is_repeated'] == false && $previous_hour_type == '') {
        $content .= '<div class= "hour-row">';
        $content .= '<div class ="flex-item">'.'<b>Hours:</b>'.'</div>';
        $content  .= '<div class ="flex-item">'.ucwords(str_replace('_', ' ', $hour['type'])) .'</div>';
        $content .= '<div class ="flex-item">'.$hour['days'] ."&nbsp" . $hour['from'] . " - " . $hour['to'] .'</div>';
        $content .= '</div>';
        //$content .= "['mon_fri'] != 1 && ['is_repeated'] == false && previous_hour_type == empty and". $hour['type'];
        $previous_hour_type =$hour['type'];
    } 

    if ( $hour['mon_fri'] != 1 && $hour['is_repeated'] == false && $previous_hour_type != $hour['type']) {
      $content .= '<div class= "hour-row">';
      $content .= '<div class ="flex-item">'.'<b>Hours:</b>'.'</div>';
      $content .=  '<div class ="flex-item">'.ucwords(str_replace('_', ' ', $hour['type'])) . ":". "</div>" ;
      $content .= '<div class ="flex-item">'.$hour['days'] ."&nbsp" . $hour['from'] . " - " . $hour['to'] .'</div>';
      $content .= '</div>';
      //$content .= "['mon_fri'] != 1 && ['is_repeated'] == false && previous_hour_type == hour type";
      $previous_hour_type =$hour['type'];
     }

    if ( $hour['mon_fri'] != 1 && $hour['is_repeated'] == true) {
      $content .= '<div class= "hour-row">';
      $content .= '<div class ="flex-item">'.'<b>Hours:</b>'.'</div>';
      $content .= '<div class ="flex-item no-ht">'.ucwords(str_replace('_', ' ', $hour['type'])) .'</div>';
      $content .=  '<div class ="flex-item">'.$hour['days'] ."&nbsp" . $hour['from'] . " - " . $hour['to'] .'</div>';
      $content .= '</div>';
      //$content .= "['mon_fri'] != 1 && ['is_repeated'] == true";
   } 

   //defaults to building hours
    if ( $hour['mon_fri'] == 1 ) {
      $content .= $hour['days'];
      $content .= '<div class= "hour-row repeated">';
      $content .= '<div class ="flex-item">'.'<b>Hours:</b>'.'</div>';
      $content .= '<div class ="flex-item no-ht">'.ucwords(str_replace('_', ' ', $hour['type'])) .'</div>';
      $content .= '<div class ="flex-item">'.$hour['days'] ."&nbsp" . $hour['from'] . " - " . $hour['to'] .'</div>';
      $content .= '</div>';
      //$content .= "['mon_fri'] == 1 && ['is_repeated'] == true  last option building hours";
    } 
// make division 3 column for service hors type hors

  }
  $content .= '</div>';
   // Always display additional information at the end
   //$content .= "<b>Additional Info:</b> " . $services['additional_info'] . "<br />";
   return $content;
}
 