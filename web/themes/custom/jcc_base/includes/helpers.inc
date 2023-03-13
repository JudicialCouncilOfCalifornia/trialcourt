<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Implements helper function.
 */
function _jcc_list_faceted_terms($node, $vocabularies) {
  $tags_markup = '';
  $unique_tags = [];
  foreach ($vocabularies as $tag) {
    $display_options = EntityViewDisplay::collectRenderDisplay($node, 'teaser')
      ->getComponent($tag);
    $tag_render = [];
    $tag_render = $node->$tag->view($display_options);

    // Remove duplicate term if the same name exists in another vocab.
    if (isset($tag_render['#items']) && $tag_render['#items']) {
      foreach ($tag_render['#items'] as $key => $item) {
        $name = strtolower($item->get('entity')
          ->getTarget()
          ->getValue()
          ->getName());

        if (!in_array($name, $unique_tags)) {
          $unique_tags[] = $name;
        }
        else {
          unset($tag_render[$key]);
        }
      }
    }

    $tags_markup .= render($tag_render);
  }

  return $tags_markup;
}

/**
 * Implements helper function,.
 */
function _jcc_get_first_image($node, $style = 'max_325x325') {
  $node_images = $node->get('field_images')->first();
  if (!empty($node_images->entity) && $node_images->entity->hasField('field_media_image')) {
    $node_image = $node_images->entity->get('field_media_image');

    if ($node_image) {
      $uri = $node_image->first()->entity->getFileUri();
      $style = \Drupal::entityTypeManager()->getStorage('image_style')->load($style);
      $image_url = _jcc_components_remove_image_protocol($style->buildUrl($uri));
      $image_alt = $node_image->first()->alt;

      return [
        'url' => $image_url,
        'alt' => $image_alt,
      ];
    }
  }

  return FALSE;
}

/**
 * Helper function to remove protocol from image paths.
 *
 * @param string $image
 *   The Drupal image url.
 *
 * @return string
 *   Converted url with no http or https.
 */
function _jcc_components_remove_image_protocol($image) {
  return preg_replace('(^https?:||^http?:)', '', $image);
}
