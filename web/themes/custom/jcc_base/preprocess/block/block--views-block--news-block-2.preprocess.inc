<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

use Drupal\Core\Render\Markup;
use Drupal\node\NodeInterface;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_block__views_block__news_block_2(&$variables) {
  $cards = [];

  if (isset($variables['elements']['content']['#view'])) {
    $rows = $variables['elements']['content']['#view']->result;

    foreach ($rows as $row) {
      $title = $row->_entity->title->value;
      $excerpt = $row->_entity->field_tagline->value;
      $url = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $row->_entity->nid->value);

      // Placeholder if no image available.
      $img_elem = Markup::create('<a href="' . $url . '" data-colorbox-gallery="gallery-beforeafter"><div class="placeholder-photo"><span class="fas fa-camera" aria-label="Camera icon indicating no image provided"></span></div></a>');

      if (isset($row->_entity->field_images)) {
        $first_image = _jcc_get_first_image($row->_entity);
        if ($first_image) {
          $img_elem = Markup::create('<a href="' . $url . '" data-colorbox-gallery="gallery-beforeafter"><img src="' . $first_image['url'] . '" alt="' . $first_image['alt'] . '"></a>');
        }
      }

      $moduleHandler = \Drupal::service('module_handler');
      $user = \Drupal::currentUser();
      $placeholder = FALSE;
      if ($moduleHandler->moduleExists('contextual') && $user->hasPermission('access contextual links')) {
        $contextual_links = [
          'node' => [
            'route_parameters' => [
              'node' => $row->_entity->id(),
            ],
          ],
        ];
        $placeholder = [
          '#type' => 'contextual_links_placeholder',
          '#id' => _contextual_links_to_id($contextual_links),
        ];
      }

      $featured_news_card = [
        'title' => $title,
        'excerpt_img' => $img_elem,
        'excerpt_img_only' => TRUE,
        'excerpt' => $excerpt,
        'url' => $url,
        'contextual_links' => $placeholder,
      ];
      $cards[] = $featured_news_card;
    }
  }

  $variables['featured_news'] = [
    'highlight_first' => TRUE,
    'highlight_variant' => "large",
    'cards' => $cards,
  ];
}

/**
 * Helper function to retrieve the first image of a node.
 *
 * @param \Drupal\node\NodeInterface $node
 *   The node entity.
 *
 * @return array|bool
 *   An array with 'url' and 'alt' keys representing the image URL and alt text,
 *   or FALSE if no image is found.
 */
function _jcc_get_first_image(NodeInterface $node) {
  $node_images = $node->get('field_images')->first();
  if (!empty($node_images->entity) && $node_images->entity->hasField('field_media_image')) {
    $node_image = $node_images->entity->get('field_media_image');

    if ($node_image) {
      $uri = $node_image->first()->entity->getFileUri();
      $style = \Drupal::entityTypeManager()->getStorage('image_style')->load('crop_16x9');
      $image_url = $style->buildUrl($uri);
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
 * Get an image from a specific field on a node.
 *
 * @param NodeInterface $node
 *   The node entity.
 * @param string $field_name
 *   The field name containing the image.
 * @param string|null $image_style
 *   The image style to apply (optional).
 *
 * @return array|false
 *   Returns an array with 'url' and 'alt' if an image is found, or FALSE otherwise.
 */
function _jcc_get_image_by_field(NodeInterface $node, string $field_name, string $image_style = NULL) {
  if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
    return FALSE;
  }

  $image_field = $node->get($field_name)->first();
  if ($image_field) {
    // Get the referenced media entity safely
    $image_entity = $image_field->get('target_id')->entity ?? NULL;

    if ($image_entity && $image_entity->hasField('field_media_image') && !$image_entity->get('field_media_image')->isEmpty()) {
      $image = $image_entity->get('field_media_image')->first();
      $uri = $image->entity->getFileUri();
      $alt = $image->alt ?? '';

      // Apply image style if provided
      if ($image_style) {
        $style = \Drupal::entityTypeManager()->getStorage('image_style')->load($image_style);
        $url = $style ? $style->buildUrl($uri) : file_create_url($uri);
      } else {
        $url = file_create_url($uri);
      }

      return [
        'url' => $url,
        'alt' => $alt,
      ];
    }
  }

  return FALSE;
}
