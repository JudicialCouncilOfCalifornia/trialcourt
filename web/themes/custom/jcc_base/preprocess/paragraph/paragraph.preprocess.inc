<?php

/**
 * @file
 * Utility code related to paragraphs.
 */

use Drupal\image\Entity\ImageStyle;

/**
 * Implements hook_preprocess_paragraph().
 */
function jcc_base_preprocess_paragraph(&$variables) {

  // Get the url of the background image if set.
  if (isset($variables['content']['field_paragraph_background_image'][0]) && isset($variables['content']['field_paragraph_background_image'][0]['#media'])) {
    $image_style = ImageStyle::load('max_2600x2600');
    if ($image_style) {
      $image_url = $image_style->buildUrl($variables['content']['field_paragraph_background_image'][0]['#media']->field_media_image->entity->getFileUri());
      $variables['background_image_url'] = _jcc_components_remove_image_protocol($image_url);
    }
  }

}
