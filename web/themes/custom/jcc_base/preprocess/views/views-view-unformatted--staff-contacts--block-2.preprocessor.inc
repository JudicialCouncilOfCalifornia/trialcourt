<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

/**
 * Implements hook_preprocess_HOOK().
 */

/**
 * Supports person per tag result, sorted alphabetically by name and then tag.
 */
function jcc_base_preprocess_views_view_unformatted__staff_contacts__block_2(&$variables) {
  if (empty($variables['rows'])) {
    return;
  }

  $persons = [];

  foreach ($variables['rows'] as $row) {
    $row = $row['content']['#node'];
    $person_id = $row->nid->value;
    $first_name = $row->field_first_name->value;
    $last_name = $row->field_last_name->value;
    if ($row->field_person_type[0]) {
      $person_type = $row->field_person_type[0]->entity->label();
    }
    $email = $row->field_email->value;
    $phone = $row->field_phone->value;
    // County information.
    $location_name = '';
    $location_website = '';
    $location = $row->field_county->entity;
    if ($row->field_county->entity) {
      $location_name = $location->title->value;
      $location_website = $location->field_website->uri;
    }
    // Contact category/subject by tagging.
    $tag_count = count($row->field_tags);
    if ($tag_count == 1) {
      $tag = (isset($row->field_tags[0]->entity)) ? $row->field_tags[0]->entity->label() : '';
    }
    else {
      // Assigns one tag per duplicated person if person has multiple tags.
      foreach ($row->field_tags as $tag_entity) {
        $tag_label = $tag_entity->entity->label();
        $existing = [
          'person_id' => $person_id,
          'location_name' => $location_name,
          'location_website' => $location_website,
          'first_name' => $first_name,
          'last_name' => $last_name,
          'title' => $person_type,
          'email' => $email,
          'phone' => $phone,
          'tag' => $tag_label,
        ];
        if (!in_array($existing, $persons)) {
          $tag = $tag_label;
        }
      }
    }

    $person = [
      'person_id' => $person_id,
      'location_name' => $location_name,
      'location_website' => $location_website,
      'first_name' => $first_name,
      'last_name' => $last_name,
      'title' => $person_type,
      'email' => $email,
      'phone' => $phone,
      'tag' => $tag,
    ];

    array_push($persons, $person);
  }

  // Sort persons by county.
  $courts = array_column($persons, 'location_name');
  array_multisort($courts, SORT_ASC, $persons);

  $variables['contacts_by_tag'] = $persons;
}
