<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

use Drupal\Core\Render\Markup;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_block__views_block__multimedia_block_1(&$variables) {
  $header_title = empty($variables['elements']['#configuration']['views_label']) ? $variables['elements']['content']['#title']['#markup'] : $variables['elements']['#configuration']['views_label'];

  if (isset($variables['elements']['content']['#view'])) {
    $cards = [];
    $rows = $variables['elements']['content']['#view']->result;
    $featured = $rows[0];
    $url = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $featured->_entity->nid->value);
    if ($featured->_entity->field_videos != NULL && !empty($featured->_entity->field_videos->getValue())) {
      $video = $featured->_entity->field_videos->getValue();
      // Get  video entity by target id from media field.
      $video_entity = Media::load($video[0]["target_id"]);
      // Video title.
      $video_title = $video_entity->label();
      // Video embed.
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('media');
      $build = $view_builder->view($video_entity, 'default');
      $video_elem = render($build);

      $featured_video_card = [
        'title' => $video_title,
        'url' => $url,
        'excerpt_vid' => $video_elem,
      ];

      array_push($cards, $featured_video_card);
    }

    if (!empty($featured->_entity->field_images)) {
      $images = $featured->_entity->field_images->getValue();
      if (!empty($featured->_entity->field_videos->getValue())) {
        $image_max = 2;
      }
      else {
        $image_max = 3;
      }

      foreach (array_slice($images, 0, $image_max) as $image) {
        // Get image entity by target id from media field.
        $image_entity = Media::load($image["target_id"]);
        // Get image url, alt, and title.
        $image_src = ImageStyle::load('max_1300x1300')->buildUrl($image_entity->field_media_image->entity->uri->value);
        $image_thumb = ImageStyle::load('medium')->buildUrl($image_entity->field_media_image->entity->uri->value);
        $image_alt = $image_entity->field_media_image->alt;
        $image_caption = strip_tags($image_entity->field_media_caption->value);
        // Image element.
        $image_elem = Markup::create('<a aria-hidden="true" href="' . $image_src . '" class="colorbox cboxElement" data-colorbox-gallery="gallery-beforeafter" title="' . $image_caption . '"><img src="' . $image_thumb . '" alt="' . $image_alt . '"></a><img class="usa-sr-only" src="' . $image_src . '" alt="' . $image_alt . '" />');

        $featured_image_card = [
          'url' => $url,
          'excerpt_img' => $image_elem,
        ];

        array_push($cards, $featured_image_card);
      }
    }

    if ($variables['elements']['content']['#view']->display_handler->display['display_options']['use_more'] == TRUE) {
      $button_text = $variables['elements']['content']['#view']->display_handler->display['display_options']['use_more_text'];
      $button_url = '/multimedia';

      $button = [
        'text' => $button_text,
        'url' => $button_url,
        'style' => 'primary',
      ];
    }
    else {
      $button = [];
    }

    $variables['featured_multimedia'] = [
      'headergroup' => [
        'title' => htmlspecialchars_decode($header_title),
      ],
      'highlight_first' => TRUE,
      'cards' => $cards,
      'button' => $button,
    ];
  }
  else {
    $variables['featured_multimedia'] = [
      'headergroup' => [
        'title' => htmlspecialchars_decode($header_title),
      ],
    ];
  }
}
