<?php

/**
 * @file
 * Preprocess and functions for paragraphs.
 */

use Drupal\Component\Utility\Html;

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

  // Topics.
  $topic_names = [];
  if ($media->hasField('field_topic')) {
    $topic_terms = $media->get('field_topic')->referencedEntities();
    foreach ($topic_terms as $term) {
      $topic_names[] = [
        'label' => $term->getName(),
      ];
    }
  }

  // Details.
  $details = [];
  $division_tid = $media->hasField('field_division') ? $media->get('field_division')->target_id : FALSE;
  $case_tid = $media->hasField('field_case_type') ? $media->get('field_case_type')->target_id : FALSE;
  $document_tid = $media->hasField('field_document_type') ? $media->get('field_document_type')->target_id : FALSE;
  $document_type = $document_tid && !empty($taxonomy_manager->load($document_tid)) ? $taxonomy_manager->load($document_tid)->getName() : '';
  $category_tid = $media->hasField('field_publication_category') ? $media->get('field_publication_category')->target_id : FALSE;
  if ($division_tid  && !empty($taxonomy_manager->load($division_tid))) {
    $details[] = [
      'division' => $taxonomy_manager->load($division_tid)->getName(),
    ];
  }
  if ($case_tid  && !empty($taxonomy_manager->load($case_tid))) {
    $details[] = [
      'case_type' => $taxonomy_manager->load($case_tid)->getName(),
    ];
  }

  // Documents.
  $lang_label_sets = __jcc_language_sets();
  $documents = [];
  $languages = [];
  foreach ($media as $field) {
    // Get language variant from field label.
    $lang_variant = trim(str_replace(['File', 'file'], '', $field->getFieldDefinition()->getLabel()));
    $lang_machine_name = strtolower(str_replace(' ', '_', $lang_variant));

    if ($field->getFieldDefinition()->getType() == 'file') {
      // File management.
      switch ($field->getName()) {
        case 'field_media_file':
        case 'field_media_file_multiple':
          // Primary English files.
          foreach ($field as $primary_file) {
            $file = \Drupal::entityTypeManager()->getStorage('file')->load($primary_file->target_id);
            if ($file) {
              $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->uri->value);
              $file_name_parts = explode('.', $file->getFileName());
              $file_extension = end($file_name_parts);
              $file_extension = strtoupper($file_extension);
              $filename = $file->getFilename();
              $short_filename = (strlen($filename) > 20) ? substr($filename, 0, 20) . '...' : $filename;
              $aria_label = t('Download or view @name @type', [
                '@name' => $media->getName(),
                '@type' => $document_type . ' ' . $short_filename,
              ]);
              $documents[] = [
                'extension' => $file_extension,
                'url' => $file_url,
                'type' => $short_filename,
                'document_type' => $document_type,
                'aria_label' => $aria_label,
              ];
            }
          }
          break;

        default:
          // Filter for translated files.
          // Match field labels with language set labels.
          $lang_key = 'field_' . $lang_machine_name . '_url';
          if (isset([$lang_label_sets][0][$lang_key]) && $field->target_id) {
            $labels = [$lang_label_sets][0][$lang_key];
            $file = \Drupal::entityTypeManager()->getStorage('file')->load($field->target_id);
            if ($file) {
              $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->uri->value);
              $aria_label = t('Download or view @name @type @lang', [
                '@name' => $media->getName(),
                '@type' => $document_type,
                '@lang' => 'in ' . $labels['label_default'],
              ]);
              $languages[] = [
                'url' => $file_url,
                'label' => $labels['label_in_language'],
                'label_en' => $labels['label_default'],
                'aria_label' => $aria_label->render(),
              ];
            }
          }
      }
    }
  }
  $publication = [
    'details' => $details,
    'body' => $media->field_media_text->view('default'),
    'primary_file_type' => $documents ? $documents[0]['extension'] : '',
    'type' => $document_type,
    'documents' => $documents,
    'languages' => $languages,
    'category' => $category_tid  && !empty($taxonomy_manager->load($category_tid)) ? $taxonomy_manager->load($category_tid)->getName() : '',
    'topics' => $topic_names,
    'date' => $media->field_date->value ? $media->field_date->date->format('F j, Y') : '',
  ];

  $variables['publication'] = $publication;
}
