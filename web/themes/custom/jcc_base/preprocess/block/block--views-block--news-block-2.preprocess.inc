<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

use Drupal\Core\Render\Markup;

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
        $first_image = _jcc_get_first_image($row->_entity, 'max_1300x1300');
        if ($first_image) {
          $img_elem = Markup::create('<a href="' . $url . '" data-colorbox-gallery="gallery-beforeafter"><img src="' . $first_image['url'] . '" alt="' . $first_image['alt'] . '"></a>');
        }
      }

      $moduleHandler = Drupal::service('module_handler');
      $user = Drupal::currentUser();
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
      array_push($cards, $featured_news_card);
    }
  }

  $variables['featured_news'] = [
    'highlight_first' => TRUE,
    'highlight_variant' => "large",
    'cards' => $cards,
  ];
}
