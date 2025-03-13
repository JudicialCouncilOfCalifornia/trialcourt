<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

use Drupal\Core\Render\Markup;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_newsroom_preprocess_block__views_block__news_block_2(&$variables) {


//  \Drupal::logger('my_module')->notice($message);
//// Logs an error
  \Drupal::logger('my_module')->error("hitting jcc_newsroom_preprocess_block__views_block__news_block_2");


  $cards = [];

  if (isset($variables['elements']['content']['#view'])) {
    $rows = $variables['elements']['content']['#view']->result;

    foreach ($rows as $row) {
      $title = $row->_entity->title->value;
      $excerpt = $row->_entity->field_tagline->value;
      $url = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $row->_entity->nid->value);

      // Placeholder if no image available.
      $img_elem = Markup::create('<a href="' . $url . '" data-colorbox-gallery="gallery-beforeafter"><div class="placeholder-photo"><span class="fas fa-camera" aria-label="Camera icon indicating no image provided"></span></div></a>');

      // Use promotion image or get first gallery image.
      if (isset($row->_entity->field_promotion_image->target_id)) {
        $promo_media_id = $row->_entity->get('field_promotion_image')->target_id;
        if (isset($promo_media_id)) {
          $promo_media = Media::load($promo_media_id);
          $promo_img_file = File::load($promo_media->field_media_image->target_id);
          if ($promo_img_file) {
            $file_uri = $promo_img_file->getFileUri();
            $img_style = \Drupal::entityTypeManager()->getStorage('image_style')->load('crop_16x9');
            $promo_image_url = _jcc_components_remove_image_protocol($img_style->buildUrl($file_uri));
            $promo_image_alt = $promo_img_file->alt;

            $img_elem = Markup::create('<a href="' . $url . '" data-colorbox-gallery="gallery-beforeafter"><img src="' . $promo_image_url . '" alt="' . $promo_image_alt . '"></a>');
          }
        }
      }

      //      new-109
      //  i deeply doubt if this caused the problem: always the first get grabbed
      //      elseif (isset($row->_entity->field_images)) {
      //
      //        $first_image = _jcc_get_first_image($row->_entity, 'crop_16x9');
      //        if ($first_image) {
      //          $img_elem = Markup::create('<a href="' . $url . '" data-colorbox-gallery="gallery-beforeafter"><img src="' . $first_image['url'] . '" alt="' . $first_image['alt'] . '"></a>');
      //        }
      //      }
      elseif (isset($row->_entity->field_promotional_image) && !$row->_entity->field_promotional_image->isEmpty()) {
        // Use promotional image if available
        \Drupal::logger('newsroom_awu')->error("hitting if jcc_newsroom_preprocess_block__views_block__news_block_2");
        $promotional_image = _jcc_get_first_image($row->_entity, 'field_promotional_image', 'crop_16x9');
        if ($promotional_image) {
          $img_elem = Markup::create('<a href="' . $url . '" data-colorbox-gallery="gallery-beforeafter"><img src="' . $promotional_image['url'] . '" alt="' . $promotional_image['alt'] . '"></a>');
        }
      } elseif (isset($row->_entity->field_images) && !$row->_entity->field_images->isEmpty()) {
        \Drupal::logger('newsroom_awu')->error("hitting else jcc_newsroom_preprocess_block__views_block__news_block_2");
        // Fallback to first image from field_images
        $first_image = _jcc_get_first_image($row->_entity, 'field_images', 'crop_16x9');
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


  \Drupal::logger('newsroom_awu')->error("exiting jcc_newsroom_preprocess_block__views_block__news_block_2");
}
