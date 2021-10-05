<?php

/**
 * @file
 * Preprocess and functions for media.
 */

use Drupal\paragraphs\ParagraphInterface;
use Drupal\views\Views;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_professional_preprocess_paragraph(&$variables) {
  $paragraph = $variables['paragraph'];
  $function = "jcc_professional_paragraph_" . $paragraph->bundle();
  if (function_exists($function)) {
    $function($variables, $paragraph);
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function jcc_professional_theme_suggestions_paragraph_alter(&$suggestions, $variables) {
  $paragraph = $variables['elements']['#paragraph'];
  if ($paragraph->bundle() == 'views_reference') {
    if (!empty($paragraph->field_views_reference)) {
      $target_id = $paragraph->field_views_reference->first()->target_id;
      $display_id = $paragraph->field_views_reference->first()->display_id;
      $suggestion = 'paragraph__' . $paragraph->bundle();
      $suggestion = !empty($target_id) ? $suggestion . "__$target_id" : $suggestion;
      $suggestion = !empty($display_id) ? $suggestion . "__$display_id" : $suggestion;
      $suggestions[] = $suggestion;
    }
  }
}

/**
 * Helper function for cards paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The paragraph.
 */
function jcc_professional_paragraph_cards(array &$variables, ParagraphInterface $paragraph) {
  $sub_variants = !empty($paragraph->field_sub_variant) ? json_decode($paragraph->field_sub_variant->first()->value, TRUE) : ['card' => 'cta'];
  // Prep the cards due to special handling of linkit links.
  $cards = $paragraph->field_components->referencedEntities();

  $variables['cards'] = [];
  foreach ($cards as $card) {
  $extra = NULL;
    $icon = [];
    $media = [];
    $bgcolor = NULL;
    $brow = NULL;
    $sub_variants['card'] = '';
    $title = !empty($card->field_heading) ? $card->field_heading->first()->value : '';

    if (!empty($card->field_link)) {
      $link = $card->field_link->view('default');
    }


    if ($card->get('field_display_document_list')->target_id) {
      $view = Views::getView('weekly_conference_results');
      $extra = $view->buildRenderable('default', [$card->get('field_display_document_list')->target_id]);
    }

    if (!empty($card->field_brow)) {
      $brow = $card->field_brow->view('default');
    }

    if (!empty($card->field_dark_brow) && $card->field_dark_brow->value == '1' && $card->field_brow->value != NULL) {
      $sub_variants['card'] = 'cta-color';
    }

    if ($card->field_icon->value) {
      $icon['name'] = str_replace("icon-fa-", "", $card->field_icon->value);
    }

    if (!$card->field_image_media->isEmpty()) {
      $img_media = Media::load($card->field_image_media[0]->getValue('target_id')['target_id']);
      $fid = $img_media->field_media_image->target_id;
      $file = File::load($fid);
      $styled_image_url = ImageStyle::load('hero_banner')->buildUrl($file->getFileUri());

      $bgcolor = $card->field_overlay->value;
      $media['src'] = $styled_image_url;
      $media['alt'] = $title;
      if ($card->field_top_background->value) {
        if ($card->field_overlay->value) {
          $sub_variants['card'] = 'media-top-overlay';
        }
        else {
          $sub_variants['card'] = 'media-top';
        }
      }
      else {
        if ($card->field_overlay->value) {
          $sub_variants['card'] = 'media-full-overlay';
        }
        else {
          $sub_variants['card'] = 'media-full';
        }
      }
    }

    $variables['cards'][] = [
      'style' => $sub_variants['card'],
      'title' => $title,
      'extra_content' => $extra,
      'brow' => $brow,
      'body' => $card->field_text->view('default'),
      'icon' => $icon,
      'link' => [
        'url' => !empty($link[0]) ? $link[0]['#url']->toString() : NULL,
        'text' => !empty($link[0]) ? $link[0]['#title'] : NULL,
        'type' => !empty($link[0]) ? _jcc_components_get_url_type($link[0]['#url']->toString()) : NULL,
      ],
      'classes' => [],
      'bgcolor' => $bgcolor,
      'media' => $media,
    ];
  }
}
