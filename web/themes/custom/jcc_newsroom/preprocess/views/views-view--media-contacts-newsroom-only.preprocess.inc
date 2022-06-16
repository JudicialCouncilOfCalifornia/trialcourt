<?php

/**
 * @file
 * Preprocessors for views.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_newsroom_preprocess_views_view__media_contacts_newsroom_only(&$variables) {
  if (empty($variables['rows'])) {
    return;
  }
  $title = $variables['view_array']['#title']['#markup'];
  $persons = [];

  foreach ($variables['rows'][0]['#rows'] as $row) {
    $personRecord = $row['#row'];
    $person_id = $personRecord->nid;
    $url = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $personRecord->_entity->nid->value);
    $first_name = $personRecord->_entity->field_first_name->value;
    $last_name = $personRecord->_entity->field_last_name->value;
    if ($personRecord->_entity->field_person_type[0]) {
      $person_type = $personRecord->_entity->field_person_type[0]->entity->label();
    }
    $email = $personRecord->_entity->field_email->value;
    $phone = $personRecord->_entity->field_phone->value;
    // County information.
    $location_name = '';
    $location_website = '';
    $location = $personRecord->_entity->field_county->entity;
    if ($personRecord->_entity->field_county->entity) {
      $location_name = $location->title->value;
      $location_website = $location->field_website->uri;
    }
    // Contact category/subject by tagging.
    $tag_count = count($personRecord->_entity->field_tags);
    if ($tag_count == 1) {
      $tag = $personRecord->_entity->field_tags[0]->entity->label();
    }
    else {
      // Assigns one tag per duplicated person if person has multiple tags.
      foreach ($personRecord->_entity->field_tags as $tag_entity) {
        $tag_label = $tag_entity->entity->label();
        $existing = [
          'person_id' => $person_id,
          'url' => $url,
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
      'url' => $url,
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

  // Group persons by tag.
  $persons_by_tag = [];
  foreach ($persons as $value) {
    if ($value['tag']) {
      $tag = $value['tag'];
      $persons_by_tag[$tag][] = $value;
    }
  }
  // Sort tag groups alphabetically.
  ksort($persons_by_tag);

  $variables['title'] = $title;
  $variables['persons_by_tag'] = $persons_by_tag;

}
