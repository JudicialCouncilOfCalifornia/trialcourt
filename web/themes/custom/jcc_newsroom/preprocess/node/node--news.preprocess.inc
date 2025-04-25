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

/**
 * A utility function currently only used here
 * Builds a valid internal URL using inference
 *
 * @param string $base
 *   The base path (e.g., 'news').
 * @param string $page
 *   The page part (e.g., 'abc').
 *
 * @return string
 *   A properly formed internal URL string, like '/news/abc'.
 */
function build_internal_url_using_inference($base, $page) {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
  $host = $_SERVER['HTTP_HOST'];
  $current_url = $scheme . "://" . $host;
  $path = '/'. trim($base, '/') . '/' . trim($page, '/');
  return Url::fromUri($current_url . $path)->toString();
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

  // NEW-35 ensure the links be correct
  if (!empty($variables['content']['field_topics'])) {
    $label_or_title_to_url_mapping = [];
    $raw_path_map = [
      "Supreme Court" => "supreme-court",
      "Courts of Appeal" => "courts-of-appeal",
      "Superior Courts" => "supreme-court",
      "Judicial Council" => "judicial-council",
      "Chief Justice" => "chief-justice",
    ];
    foreach ($raw_path_map as $title => $path) {
      $label_or_title_to_url_mapping[$title] = build_internal_url_using_inference("news", $path);
    }

    $url = "internal:/search" ;
    foreach ($variables['content']['field_topics']['#items'] as $delta => $item) {
      if (!empty($variables['content']['field_topics']) && is_array($variables['content']['field_topics'])) {
        foreach ($variables['content']['field_topics'] as  &$field_item) {
          if (is_array($field_item) && isset($field_item['#url'])) {
            $title = $field_item['#title'];
            if ($field_item['#url'] instanceof Url) {
              if($label_or_title_to_url_mapping[$title]){
                $url = $label_or_title_to_url_mapping[$title];
              }
              $field_item['#url'] = Url::fromUri($url);
            }
          }
        }
      }
    }
  }
}
