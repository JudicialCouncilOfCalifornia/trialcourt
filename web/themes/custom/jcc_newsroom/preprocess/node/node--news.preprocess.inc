<?php

/**
 * @file
 * Theme and preprocess functions for news node.
 */

use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

/**
 * @file
 * Theme and preprocess functions for nodes.
 */

$helper_path = DRUPAL_ROOT . '/themes/custom/jcc_base/includes/helper.inc';
if (file_exists($helper_path)) {
  require_once $helper_path;
}

/**
 * Implements hook_preprocess_node__news().
 * NEW-35: for a mapped link, click on it then the app will route to its destination
 *
 * @param array $variables
 *   An array of variables passed to the theme hook.
 *
 * @param string $hook
 *   The base hook name.
 *
 * @return array
 *   An array.
 */
function jcc_newsroom_preprocess_node__news(&$variables, $hook) {
  // Setting Feature typed News hero image.
  if (isset($variables['content']['field_images']['#items'])
    && $variables['content']['field_images']['#items'] != NULL
    && isset($variables['content']['field_news_type']['#items'])
    && $variables['content']['field_news_type']['#items'] != NULL
    && $variables['content']['field_news_type']['#items']->first()->entity->label() == 'Feature') {

    $images = $variables['content']['field_images']['#items'];

    $referenced_img = $images->first()->get('target_id')->getValue();
    $media = Media::load($referenced_img);
    if ($media) {
      $fid = $media->field_media_image->target_id;
      $file = File::load($fid);
      if ($file) {
        $file_uri = $file->getFileUri();
        $style = \Drupal::entityTypeManager()
          ->getStorage('image_style')
          ->load('crop_16x9');
        $destination_uri = $style->buildUri($file->uri->value);
        $style->createDerivative($file_uri, $destination_uri);
        $variables['hero_image'] = [
          'src' => _jcc_components_remove_image_protocol($style->buildUrl($file_uri)),
          'alt' => $media->field_media_image->alt,
          'renderer' => 'patternlab',
        ];
      }
    }
  }

  // NEW-35.
  if (!empty($variables['content']['field_topics'])) {
    $custom_url_map = build_custom_url_map();
    $url = "internal:/search";
    foreach ($variables['content']['field_topics']['#items'] as $delta => $item) {
      if (!empty($variables['content']['field_topics']) && is_array($variables['content']['field_topics'])) {
        foreach ($variables['content']['field_topics'] as &$field_item) {
          if (is_array($field_item) && isset($field_item['#url'])) {
            $title = $field_item['#title'];
            if ($field_item['#url'] instanceof Url) {
              if ($custom_url_map[$title]) {
                $url = $custom_url_map[$title];
              }
              $field_item['#url'] = Url::fromUri($url);
            }
          }
        }
      }
    }
  }
}
