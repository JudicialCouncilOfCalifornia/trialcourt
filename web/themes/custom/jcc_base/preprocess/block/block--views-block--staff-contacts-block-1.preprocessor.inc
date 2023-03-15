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
function jcc_base_preprocess_block__views_block__staff_contacts_block_1(&$variables) {
  if (isset($variables['elements']['content']['#view'])) {
    $persons = [];
    $rows = $variables['elements']['content']['#view']->result;

    foreach ($rows as $row) {
      $person_id = $row->nid;
      $url = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $row->_entity->nid->value);
      $first_name = $row->_entity->field_first_name->value;
      $last_name = $row->_entity->field_last_name->value;
      if ($row->_entity->field_person_type[0]) {
        $person_type = $row->_entity->field_person_type[0]->entity->label();
      }
      $email = $row->_entity->field_email->value;
      $phone = $row->_entity->field_phone->value;
      // County information.
      $location_name = '';
      $location_website = '';
      $location = $row->_entity->field_county->entity;
      if ($row->_entity->field_county->entity) {
        $location_name = $location->title->value;
        $location_website = $location->field_website->uri;
      }
      // Contact category/subject by tagging.
      $tag_count = count($row->_entity->field_tags);
      if ($tag_count == 1) {
        $tag = (isset($row->_entity->field_tags[0]->entity)) ? $row->_entity->field_tags[0]->entity->label() : '';
      }
      else {
        // Assigns one tag per duplicated person if person has multiple tags.
        foreach ($row->_entity->field_tags as $tag_entity) {
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

    $variables['persons_by_tag'] = $persons_by_tag;
  }
}
