<?php

/**
 * @file
 * Preprocess and functions for paragraphs.
 */

use Drupal\Component\Utility\Html;
use Drupal\file\Entity\File;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_elevated_preprocess_media(&$variables) {
  $media = $variables['media'];
  if ($media->bundle() == 'image') {
    $view_mode = $variables['view_mode'];

    $alignment = '';
    if (isset($variables['attributes']['class'])) {
      $alignment = $variables['attributes']['class'][0] ?? '';
    }

    if ($media->hasField('field_media_image') && !$media->get('field_media_image')->isEmpty()) {
      $image_field = $media->get('field_media_image')->entity;
      if ($image_field && $image_field->getMimeType() == 'image/gif') {
        unset($variables['content']['field_media_image'][0]['#image_style']);
      }
    }
    $img_classes = [
      'image',
      Html::getClass('image__' . $view_mode),
      Html::getClass($alignment),
    ];

    $variables['content']['field_media_image'][0]['#item_attributes']['class'][] = implode(' ', $img_classes);
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_elevated_preprocess_media__publication(&$variables) {
  $media = $variables['media'];
  $entity_type_manager = \Drupal::service('entity_type.manager');
  $taxonomy_manager = $entity_type_manager->getStorage('taxonomy_term');

  // Documents.
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
          if (($lang_variant == $label['label_default']) && $field->target_id) {
            $file = File::load($field->target_id);
            $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->uri->value);
            $languages[] = [
              'url' => $file_url,
              'label' => $label['label_in_language'],
              'label_en' => $label['label_default'],
            ];
          }
        }
      }
    }
  }

  $topic_names = [];
  if ($media->hasField('field_topic')) {
    $topic_terms = $media->get('field_topic')->referencedEntities();
    foreach ($topic_terms as $term) {
      $topic_names[] = [
        'label' => $term->getName(),
      ];
    }
  }

  $division_tid = $media->hasField('field_division') ? $media->get('field_division')->target_id : FALSE;
  $case_tid = $media->hasField('field_case_type') ? $media->get('field_case_type')->target_id : FALSE;
  $document_tid = $media->hasField('field_document_type') ? $media->get('field_document_type')->target_id : FALSE;
  $category_tid = $media->hasField('field_publication_category') ? $media->get('field_publication_category')->target_id : FALSE;

  $publication = [
    'details' => [
      'division' => $division_tid  && !empty($taxonomy_manager->load($division_tid)) ? $taxonomy_manager->load($division_tid)->getName() : '',
      'case_type' => $case_tid  && !empty($taxonomy_manager->load($case_tid)) ? $taxonomy_manager->load($case_tid)->getName() : '',
    ],
    'body' => $media->field_media_text->view('default'),
    'primary_file_type' => $documents ? $documents[0]['file_type'] : '',
    'type' => $document_tid && !empty($taxonomy_manager->load($document_tid)) ? $taxonomy_manager->load($document_tid)->getName() : '',
    'documents' => $documents,
    'languages' => $languages,
    'category' => $category_tid  && !empty($taxonomy_manager->load($category_tid)) ? $taxonomy_manager->load($category_tid)->getName() : '',
    'topics' => $topic_names,
    'date' => $media->field_date->value ? $media->field_date->date->format('F j, Y') : '',
  ];

  $variables['publication'] = $publication;
}
