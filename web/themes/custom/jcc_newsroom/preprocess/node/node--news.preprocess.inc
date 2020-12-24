<?php

/**
 * @file
 * Theme and preprocess functions for news node.
 */

use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

/**
 * @file
 * Theme and preprocess functions for nodes.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_newsroom_preprocess_node__news(&$variables, $hook) {
  // Setting Feature News image use.
  if (isset($variables['content']['field_images']['#items'])
    && $variables['content']['field_images']['#items'] != NULL
    && isset($variables['content']['field_news_type']['#items'])
    && $variables['content']['field_news_type']['#items'] != NULL
    && $variables['content']['field_news_type']['#items']->first()->entity->label() == 'Feature') {

    $images = $variables['content']['field_images']['#items'];
    $imageIdx = 1;
    $useSecondImage = FALSE;
    if (isset($variables['content']['field_second_image_as_hero']['#items'])) {
      $useSecondImage = $variables['content']['field_second_image_as_hero']['#items']->getString();
    }

    // Use first image unless preferred not.
    $referenced_img = $images->first()->get('target_id')->getValue();
    if ($useSecondImage == TRUE && count($images) != 1) {
      $imageIdx = 2;
      $referenced_img = $images[1]->get('target_id')->getValue();
    }
    $media = Media::load($referenced_img);
    if ($media) {
      $fid = $media->field_media_image->target_id;
      $file = File::load($fid);
      if ($file) {
        $file_uri = $file->getFileUri();
        $style = \Drupal::entityTypeManager()
          ->getStorage('image_style')
          ->load('crop_16x9');

        $destination_uri = $style->buildUri($file->uri->value);
        $style->createDerivative($file_uri, $destination_uri);
        $variables['hero_bckg_url'] = $style->buildUrl($file_uri);
      }
    }
    $variables['featured_image_idx'] = $imageIdx;
  }
}
