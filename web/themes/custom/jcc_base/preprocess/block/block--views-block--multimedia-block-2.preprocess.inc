<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

use Drupal\Core\Render\Markup;
use Drupal\media\Entity\Media;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_block__views_block__multimedia_block_2(&$variables) {
  if (isset($variables['elements']['content']['#view'])) {
    $header_title = empty($variables['elements']['#configuration']['views_label']) ? $variables['elements']['content']['#title']['#markup'] : $variables['elements']['#configuration']['views_label'];
    $cards = [];
    $rows = $variables['elements']['content']['#view']->result;

    if ($rows != NULL && !empty($rows)) {
      $featured = $rows[0];
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
        // Video alternate player.
        $video_alt_desc = 'View with calendar or agenda details';
        $video_origin = $video_entity->field_media_video_embed_field->value;
        $video_alternate = Markup::create('<a href="' . $video_origin . '" target="_blank">' . $video_alt_desc .'</a>');

        $featured_video_card = [
          'title' => $video_title,
          'excerpt_vid' => $video_elem,
          'footer' => $video_alternate,
        ];

        array_push($cards, $featured_video_card);
      }

      $variables['featured_webcast'] = [
        'headergroup' => [
          'title' => htmlspecialchars_decode($header_title),
        ],
        'cards' => $cards,
      ];
    }
  }
}
