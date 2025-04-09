<?php

/**
 * @file
 * Theme and preprocess functions for news node.
 */

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

/**
 * @file
 * Theme and preprocess functions for nodes.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_newsroom_preprocess_node__news(&$variables, $hook) {
  // Setting Feature typed News hero image.
  if (isset($variables['content']['field_images']['#items'])
    && $variables['content']['field_images']['#items'] != NULL
    && isset($variables['content']['field_news_type']['#items'])
    && $variables['content']['field_news_type']['#items'] != NULL
    && $variables['content']['field_news_type']['#items']->first()->entity->label() == 'Feature') {
    $variables['hero_image'] = _jcc_get_image_from_field($variables['content'], 'field_promotion_image');
    // Fallback to the first image in field_images if no promotional image.
    if (!$variables['hero_image']) {
      $variables['hero_image'] = _jcc_get_image_from_field($variables['content'], 'field_images');
    }
    if ($variables['hero_image']) {
      $variables['hero_image']['renderer'] = 'patternlab';
    }
  }
}

/**
 * Retrieves the first image URL and alt text from a media field.
 */
function _jcc_get_image_from_field(array $content, $field_name) {
  if (!empty($content[$field_name]['#items'])) {
    $image_item = $content[$field_name]['#items']->first();
    $media = Media::load($image_item->target_id);
    if ($media && $media->hasField('field_media_image') && !$media->get('field_media_image')->isEmpty()) {
      $file = File::load($media->field_media_image->target_id);
      if ($file) {
        $file_uri = $file->getFileUri();
        $style = \Drupal::entityTypeManager()->getStorage('image_style')->load('crop_16x9');
        return [
          'src' => _jcc_components_remove_image_protocol($style->buildUrl($file_uri)),
          'alt' => $media->field_media_image->alt,
        ];
      }
    }
  }
  return FALSE;
}
