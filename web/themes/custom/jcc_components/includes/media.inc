<?php

/**
 * @file
 * Preprocess and functions for paragraphs.
 */

use Drupal\Component\Utility\Html;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Term;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_media(&$variables) {
  $media = $variables['media'];

  // The following code replaces image alignment feature for WYSIWYG content.
  // Image alignment is determined by the view mode rather than editor choice.
  // If rolled back, re-enable data-align config in the text editor:
  // @see /admin/config/content/formats/manage/aside?destination=/admin/config/content/formats
  // @see /admin/config/content/formats/manage/body?destination=/admin/config/content/formats
  if ($media->bundle() == 'image') {
    $view_mode = $variables['view_mode'];
    $view_mode_alignments = [
      'container_25_percent' => 'left',
      'container_50_percent' => 'left',
      'container_75_percent' => 'center',
      'container_100_percent' => 'none',
    ];

    // Update Data Align from View Mode.
    $variables['attributes']['data-align'] = !empty($view_mode_alignments[$view_mode]) ? $view_mode_alignments[$view_mode] : 'none';

    // Add additional CSS Classes directly to Image Tag.
    $img_classes = [
      'image',
      Html::getClass('image__' . $view_mode),
      Html::getClass('align-' . $variables['attributes']['data-align']),
    ];

    $variables['content']['field_media_image'][0]['#item_attributes']['class'][] = implode(' ', $img_classes);

  }

  if ($media->bundle() == 'remote_video') {
    // Extract the video ID from the url.
    $url = $media->field_media_oembed_video->value;
    $parsed = parse_url($url);
    $query = explode('&', $parsed['query']);
    foreach ($query as $param) {
      if (strpos($param, 'v=') === 0) {
        $variables['video_id'] = str_replace('v=', '', $param);
      }
    }
    if (empty($variables['video_id'])) {
      $args = explode('/', $parsed['path']);
      $variables['video_id'] = $args[1];
    }
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_media__publication(&$variables) {
  $media = $variables['media'];
  $lang_label_sets = __jcc_language_sets();
  $documents = [];
  $languages = [];
  foreach ($media as $field) {
    if ($field->getFieldDefinition()->getType() == 'file') {
      $lang_variant = explode(' ', $field->getFieldDefinition()->getLabel());
      if ($field->getName() == 'field_media_file_multiple') {
        foreach ($field as $primary_file) {
          $file = File::load($primary_file->target_id);
          if ($file) {
            $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->uri->value);
            $file_name_parts = explode('.', $file->getFileName());
            $file_extension = end($file_name_parts);
            $documents[] = [
              'url' => \Drupal::service('file_url_generator')->transformRelative($file_url),
              'file_type' => strtoupper($file_extension),
            ];
          }
        }
      }
      else {
        foreach ($lang_label_sets as $label) {
          if (($lang_variant[0] == $label->label_default) && $field->target_id) {
            $file = File::load($field->target_id);
            $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->uri->value);
            $languages[] = [
              'url' => \Drupal::service('file_url_generator')->transformRelative($file_url),
              'label' => $label->label_in_language,
            ];
          }
        }
      }
    }
  }

  $publication = [
    'published' => $media->field_date->value ? $media->field_date->date->format('F j, Y') : '',
    'division' => $media->field_division->target_id ? Term::load($media->field_division->target_id)->get('name')->value : '',
    'topic' => $media->field_topic->target_id ? Term::load($media->field_topic->target_id)->get('name')->value : '',
    'case_type' => $media->field_case_type->target_id ? Term::load($media->field_case_type->target_id)->get('name')->value : '',
    'body' => $media->field_media_text->view('default'),
    'primary_file_type' => $documents ? $documents[0]['file_type'] : '',
    'type' => $media->field_document_type->target_id ? Term::load($media->field_document_type->target_id)->get('name')->value : '',
    'documents' => $documents,
    'languages' => $languages,
  ];

  $variables['publication'] = $publication;
}
