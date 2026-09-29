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
    if (isset($rows[0])) {
      $featured = $rows[0];
    }

    $moduleHandler = Drupal::service('module_handler');
    $user = Drupal::currentUser();
    $placeholder = FALSE;
    if ($moduleHandler->moduleExists('contextual') && $user->hasPermission('access contextual links')) {
      if (isset($rows[0]) && isset($rows[0]->_entity)) {
        $contextual_links = [
          'node' => [
            'route_parameters' => [
              'node' => $rows[0]->_entity->id(),
            ],
          ],
        ];
        $placeholder = [
          '#type' => 'contextual_links_placeholder',
          '#id' => _contextual_links_to_id($contextual_links),
        ];
      }
    }

    if (isset($rows[0]) && $featured->_entity->field_videos != NULL && !empty($featured->_entity->field_videos->getValue())) {
      $video = $featured->_entity->field_videos->getValue();
      // Get  video entity by target id from media field.
      $video_entity = Media::load($video[0]["target_id"]);
      if ($video_entity != NULL) {
        // Video title.
        $video_title = $video_entity->label();
        // ADA-318: The default media view mode renders the contrib
        // video_embed_field launcher as a <div> with only a click handler,
        // which keyboard users cannot reach or operate (WCAG 2.1.1). Build
        // the launcher as a native <button> instead.
        $video_url = $video_entity->field_media_video_embed_field->value;
        // Video id extraction matching field--field-videos.html.twig.
        $video_id = $video_url;
        foreach (['v=', '/'] as $sep) {
          $parts = explode($sep, $video_id);
          $video_id = end($parts);
        }
        foreach (['?', '&'] as $sep) {
          $parts = explode($sep, $video_id);
          $video_id = $parts[0];
        }
        $thumb_uri = $video_entity->thumbnail->entity->uri->value;
        $thumb_src = ImageStyle::load('large')->buildUrl($thumb_uri);
        $thumb_src = _jcc_components_remove_image_protocol($thumb_src);
        $thumb_alt = '';
        if ($video_entity->hasField('field_media_thumbnail_alt') && !$video_entity->field_media_thumbnail_alt->isEmpty()) {
          $thumb_alt = trim($video_entity->field_media_thumbnail_alt->value);
        }
        elseif (!empty($video_entity->thumbnail->alt)) {
          $thumb_alt = trim($video_entity->thumbnail->alt);
        }
        $modal_html = '<div class="video-embed-field-provider-youtube video-embed-field-responsive-video video-embed-field-responsive-modal" style="width:854px;"><iframe width="854" height="480" frameborder="0" allowfullscreen="allowfullscreen" src="https://www.youtube.com/embed/' . $video_id . '?autoplay=1&rel=0"></iframe></div>';
        $video_elem = Markup::create('<button type="button" class="video-embed-field-launch-modal jcc-video-launch-button" data-video-embed-field-modal="' . htmlspecialchars($modal_html, ENT_QUOTES) . '" aria-label="Play video in dialog: ' . htmlspecialchars($video_title, ENT_QUOTES) . '"><img src="' . $thumb_src . '" alt="' . htmlspecialchars($thumb_alt, ENT_QUOTES) . '" typeof="foaf:Image"></button>');
        $variables['#attached']['library'][] = 'video_embed_field/colorbox';

        $featured_video_card = [
          'title' => $video_title,
          'excerpt_vid' => $video_elem,
          'contextual_links' => $placeholder,
        ];

        array_push($cards, $featured_video_card);
      }
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
        $image_src = _jcc_components_remove_image_protocol($image_src);
        $image_thumb = ImageStyle::load('medium')->buildUrl($image_entity->field_media_image->entity->uri->value);
        $image_thumb = _jcc_components_remove_image_protocol($image_thumb);
        $image_alt = $image_entity->field_media_image->alt;
        $image_caption = strip_tags($image_entity->field_media_caption->value);
        // Image element.
        $image_elem = Markup::create('<a href="' . $image_src . '" class="colorbox cboxElement" data-colorbox-gallery="gallery-beforeafter" title="' . $image_caption . '" aria-label="Click to view larger image"><img src="' . $image_thumb . '" alt="' . $image_alt . '"></a>');

        $featured_image_card = [
          'excerpt_img' => $image_elem,
          'excerpt' => $image_caption,
          'contextual_links' => $placeholder,
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
