<?php

/**
 * @file
 * Theme and preprocess functions for news node.
 */

use Drupal\Core\Url;use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;

/**
 * @file
 * Theme and preprocess functions for nodes.
 */

/**
 * Implements hook_preprocess_HOOK().
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


  \Drupal::logger('new35')->info("entering preprocessing_for_links");
  if (!empty($variables['content']['field_topics'])) {
    \Drupal::logger('new35')->info("within the if");

    $mappings = [
      "CHIEF JUSTICE" => "",

      "COURTS OF APPEAL" => "https://appellate.courts.ca.gov/",
      "SUPREME COURT" => "https://supreme.courts.ca.gov/",
      "JUDICIAL COUNCIL" => "https://courts.ca.gov/policy-administration/judicial-council",
      "SUPERIOR COURTS" => ""
    ];
    $default_link_destination = "search";

    foreach ($variables['content']['field_topics']['#items'] as &$item) {
        \Drupal::logger('new35')->info("in the for loop of preprocessing_for_links");
        $destination = $default_link_destination;
        $text = "super";
//      $text = $item['entity']->label();
      if (isset($mappings[$text])) {
        $destination = $mappings[$text]; // Direct match
      }
//      $item['url'] = Url::fromUri('internal:/' . $destination);
    }
  }
  \Drupal::logger('new35')->info("exiting preprocessing_for_links");


}
