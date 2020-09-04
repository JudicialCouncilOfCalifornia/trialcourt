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
    if ($tag_render['#items']) {
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
