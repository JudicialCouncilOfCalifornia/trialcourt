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
 * NEW-35: for a mapped link, click on it then the app will route to its destination
 *
 * 237 => "https://supreme.courts.ca.gov/",
 * 33 => "https://appellate.courts.ca.gov/",
 * 8 => "https://courts.ca.gov/courts/superior-courts",
 * 15 => "https://courts.ca.gov/policy-administration/judicial-council",
 * 234 => "https://supreme.courts.ca.gov/about-court/justices-court/chief-justice-patricia-guerrero"
 *
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

  // NEW-35
  if (!empty($item->target_id)) {
    return;
  }

  if (!empty($variables['content']['field_topics'])) {
    $url = "internal:/search" ;
    foreach ($variables['content']['field_topics']['#items'] as $delta => $item) {
      $label_or_title_to_url_mapping = [
            "Supreme Court" => "https://supreme.courts.ca.gov/",
            "Courts of Appeal" => "https://appellate.courts.ca.gov/",
            "Superior Courts" => "https://courts.ca.gov/courts/superior-courts",
            "Judicial Council" => "https://courts.ca.gov/policy-administration/judicial-council",
            "Chief Justice"=> "https://supreme.courts.ca.gov/about-court/justices-court/chief-justice-patricia-guerrero"
      ];

      if (!empty($variables['content']['field_topics']) && is_array($variables['content']['field_topics'])) {
        foreach ($variables['content']['field_topics'] as  &$field_item) {
          if (is_array($field_item) && isset($field_item['#url'])) {
            $title = $field_item['#title'];
            if ($field_item['#url'] instanceof \Drupal\Core\Url) {
              if($label_or_title_to_url_mapping[$title]){
                $url = $label_or_title_to_url_mapping[$title];
              }
              $field_item['#url'] = \Drupal\Core\Url::fromUri($url);
            }
          }
        }
      }
    }
  }
}

