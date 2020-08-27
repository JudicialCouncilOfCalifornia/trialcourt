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
  // Forcing image field to use image_style formatting.
  if ($variables['content']['field_images']['#items'] != NULL) {
    $referenced_img = $variables['content']['field_images']['#items']->first()->get('target_id')->getValue();
    $media = Media::load($referenced_img);
    $fid = $media->field_media_image->target_id;
    $file = File::load($fid);
    $file_uri = $file->getFileUri();
    $style = \Drupal::entityTypeManager()->getStorage('image_style')->load('crop_16x9');

    $destination_uri = $style->buildUri($file->uri->value);
    $style->createDerivative($file_uri, $destination_uri);
    $variables['hero_bckg_url'] = $style->buildUrl($file_uri);
  }
}
