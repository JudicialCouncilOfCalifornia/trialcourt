<?php

/**
 * @file
 * Preprocess and functions for paragraphs.
 */

use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Term;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_elevated_preprocess_media__publication(&$variables) {
  $media = $variables['media'];
  $lang_label_sets = __jcc_language_sets();
  $documents = [];
  $languages = [];
  foreach ($media as $field) {
    if ($field->getFieldDefinition()->getType() == 'file') {
      // Get language variant from field label.
      $lang_variant = trim(str_replace(['File', 'file'], '', $field->getFieldDefinition()->getLabel()));

      // File management.
      if ($field->getName() == 'field_media_file_multiple') {
        // Primary file English variants.
        foreach ($field as $primary_file) {
          $file = File::load($primary_file->target_id);
          if ($file) {
            $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->uri->value);
            $file_name_parts = explode('.', $file->getFileName());
            $file_extension = end($file_name_parts);
            $documents[] = [
              'url' => $file_url,
              'file_type' => strtoupper($file_extension),
            ];
          }
        }
      }
      else {
        // Filter for translated files.
        // Match field labels with language set labels.
        foreach ($lang_label_sets as $label) {
          if (($lang_variant == $label->label_default) && $field->target_id) {
            $file = File::load($field->target_id);
            $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->uri->value);
            $languages[] = [
              'url' => $file_url,
              'label' => $label->label_in_language,
              'label_en' => $label->label_default,
            ];
          }
        }
      }
    }
  }

  $publication = [
    'details' => [
      'published' => $media->field_date->value ? $media->field_date->date->format('F j, Y') : '',
      'division' => $media->field_division->target_id ? Term::load($media->field_division->target_id)->get('name')->value : '',
      'topic' => $media->field_topic->target_id ? Term::load($media->field_topic->target_id)->get('name')->value : '',
      'case_type' => $media->field_case_type->target_id ? Term::load($media->field_case_type->target_id)->get('name')->value : '',
    ],
    'body' => $media->field_media_text->view('default'),
    'primary_file_type' => $documents ? $documents[0]['file_type'] : '',
    'type' => $media->field_document_type->target_id ? Term::load($media->field_document_type->target_id)->get('name')->value : '',
    'documents' => $documents,
    'languages' => $languages,
  ];

  $variables['publication'] = $publication;
}
