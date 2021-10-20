<?php

/**
 * @file
 * Preprocess and functions for media.
 */

use Drupal\paragraphs\ParagraphInterface;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\views\ViewEntityInterface;
use Drupal\views\Views;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_paragraph(&$variables) {
  $paragraph = $variables['paragraph'];
  $function = "jcc_components_paragraph_" . $paragraph->bundle();
  if (function_exists($function)) {
    $function($variables, $paragraph);
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function jcc_components_theme_suggestions_paragraph_alter(&$suggestions, $variables) {
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
function jcc_components_paragraph_cards(array &$variables, ParagraphInterface $paragraph) {
  $sub_variants = !empty($paragraph->field_sub_variant) ? json_decode($paragraph->field_sub_variant->first()->value, TRUE) : ['card' => 'cta'];
  // Prep the cards due to special handling of linkit links.
  $cards = $paragraph->field_components->referencedEntities();
  $variables['cards'] = _jcc_components_generate_cards($cards, $sub_variants);

  // Option to select a different column layouts for next row.
  if ($paragraph->field_components2) {
    // Prep the cards due to special handling of linkit links.
    $cards2 = $paragraph->field_components2->referencedEntities();
    $variables['cards2'] = _jcc_components_generate_cards($cards2, $sub_variants);
  }
}

/**
 * Helper function for action_items paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The paragraph.
 */
function jcc_components_paragraph_action_list(array &$variables, ParagraphInterface $paragraph) {
  // Prep the action list items due to special handling of linkit links.
  $items = $paragraph->field_components->referencedEntities();

  foreach ($items as $item) {
    $title = !empty($item->field_heading) ? $item->field_heading->first()->value : '';

    if (!empty($item->field_link)) {
      $link = $item->field_link->view('default');
    }

    $variables['action_items'][] = [
      'title' => $title,
      'body' => $item->field_text->view('default'),
      'link' => [
        'url' => !empty($link[0]) ? $link[0]['#url']->toString() : NULL,
        'text' => !empty($link[0]) ? $link[0]['#title'] : NULL,
      ],
      'classes' => [],
    ];
  }
}

/**
 * Helper function for hero_banner paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The paragraph.
 */
function jcc_components_paragraph_hero_banner(array &$variables, ParagraphInterface $paragraph) {
  // Set up values for icon_links to pass to the pattern library template.
  $components = $paragraph->field_components->referencedEntities();
  foreach ($components as $component) {
    if (!empty($component->field_icon)) {
      $variables['content']['icon_links'][] = [
        'icon' => !empty($component->field_icon->view('default')[0]) ? $component->field_icon->view('default')[0] : NULL,
        'link' => !empty($component->field_link->view('default')[0]) ? $component->field_link->view('default')[0] : NULL,
      ];
    }
  }
}

/**
 * Helper function for views_reference paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The paragraph.
 */
function jcc_components_paragraph_views_reference(array &$variables, ParagraphInterface $paragraph) {
  // In some cases we need to pass the views results to the paragraph.
  if (!empty($paragraph->field_views_reference)) {
    $view = $paragraph->field_views_reference->referencedEntities()[0];
    $reference = $paragraph->field_views_reference->first();
    $target_id = $reference->target_id;
    $display_id = $reference->display_id;
    $function = "jcc_components_paragraph_views_reference_${target_id}_${display_id}";
  }

  if (!empty($function) && function_exists($function)) {
    $function($variables, $view);
  }
}

/**
 * Helper function for views_reference paragrpahs of type news.
 *
 * @param array $variables
 *   The paragraph variables.
 * @param Drupal\views\ViewEntityInterface $view
 *   The news view.
 */
function jcc_components_paragraph_views_reference_news_sticky_list(array &$variables, ViewEntityInterface $view) {
  $executable = $view ? $view->getExecutable() : NULL;
  // Start with the sticky_list display.
  $sticky_list = $executable ? $executable->setDisplay('sticky_list') : FALSE;
  $display = $executable->executeDisplay();

  if ($sticky_list) {
    $variables['sticky_list'] = !empty($display['#rows']) ? $display['#rows'][0]['#rows'] : [];
  }
  // Also get the More News path. Should be a node with path /news but fallback
  // to view url if it exists.
  $view_url = !empty($executable->getUrl()) ? $executable->getUrl()->toString() : '';
  $path = \Drupal::service('path.validator')->isValid('/news') ? '/news' : $view_url;

  $variables['news_url'] = $path;
  $variables['#cache']['tags'][] = 'node_list:news';
}

/**
 * Helper function for views_reference paragraphs of type location.
 *
 * @param array $variables
 *   The paragraph variables.
 * @param Drupal\views\ViewEntityInterface $view
 *   The news view.
 */
function jcc_components_paragraph_views_reference_location_map_list(array &$variables, ViewEntityInterface $view) {
  $executable = $view ? $view->getExecutable() : NULL;

  // Start with the map_list display.
  $map_list = $executable ? $executable->setDisplay('map_list') : FALSE;
  $display = $executable->executeDisplay();

  if ($map_list) {
    $variables['map_list'] = !empty($display['#rows']) ? $display['#rows'] : [];
  }
  // Reset the executable for the map_list_attachment.
  $executable = $view ? $view->getExecutable() : NULL;
  $map_list_attachment = $executable->setDisplay('map_list_attachment');
  $display = $executable->executeDisplay();

  if ($map_list_attachment && !empty($display['#rows'])) {
    foreach ($display['#rows'][0]['#rows'] as $row) {
      $variables['location_items'][] = [
        'title' => $row['#node']->getTitle(),
        'address' => $row['#node']->field_address,
        'phone' => $row['#node']->field_phone,
      ];
    }
  }
  $variables['#cache']['tags'][] = 'node_list:location';
}

/**
 * Helper function for text share paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The paragraph.
 */
function jcc_components_paragraph_text_share(array &$variables, ParagraphInterface $paragraph) {
  if ($node = \Drupal::routeMatch()->getParameter('node')) {
    $variables['page_title'] = $node->title->value;
  }
}

/**
 * Helper function for content streams.
 *
 * @param array $variables
 *   The paragraph variables.
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The paragraph.
 */
function jcc_components_paragraph_content_stream(array &$variables, ParagraphInterface $paragraph) {
  $taxonomy = $paragraph->field_taxonomy ? $paragraph->field_taxonomy->target_id : NULL;
  $date_start = $paragraph->field_filter_by_date->start_date ? $paragraph->field_filter_by_date->start_date->format('Ymd') : NULL;
  $date_end = $paragraph->field_filter_by_date->end_date ? $paragraph->field_filter_by_date->end_date->format('Ymd') : NULL;

  if ($taxonomy) {
    // If News + Sticky listing, filter by taxonomy option.
    if ($paragraph->field_content_stream->plugin_id == 'views_block:news-sticky_list') {
      $view = Views::getView('news');
      $view->storage->invalidateCaches();
      $stream = $view->buildRenderable('sticky_list', [$taxonomy]);
      $variables['filtered_content'] = $stream;
    }
  }

  if ($date_start && $date_end) {
    // If Oral Arguments case documents listing, filter by date range.
    if ($paragraph->field_content_stream->plugin_id == 'views_block:oral_arguments-block_1') {
      $view = Views::getView('oral_arguments');
      $view->storage->invalidateCaches();
      $stream = $view->buildRenderable('default', [$date_start, $date_end]);
      $variables['filtered_content'] = $stream;
    }
  }
}

/**
 * Helper function to get the type of URL (file, etc).
 *
 * @param string $url
 *   The URL to test.
 *
 * @return string|null
 *   The type of URL.
 */
function _jcc_components_get_url_type($url) {
  $extensions = [
    'pdf',
    'zip',
    'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
  ];
  $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);

  if (in_array(strtolower($extension), $extensions)) {
    return 'file';
  }

  return NULL;
}

/**
 * Helper function for cards creation.
 */
function _jcc_components_generate_cards($cards, $sub_variants) {
  $set = [];

  foreach ($cards as $card) {
    $extra = NULL;
    $icon = [];
    $media = [];
    $bgcolor = NULL;
    $brow = NULL;
    $title = !empty($card->field_heading) ? $card->field_heading->first()->value : '';

    if (!empty($card->field_link)) {
      $link = $card->field_link->view('default');
    }

    if (!empty($card->field_brow) && $card->field_brow->value) {
      $brow = $card->field_brow->view('default');
      if (!empty($card->field_dark_brow) && $card->field_dark_brow->value == '1') {
        $sub_variants['card'] = 'cta-color';
      }
    }

    if ($card->field_display_document_list && $card->get('field_display_document_list')->target_id) {
      $view = Views::getView('documents');
      $view->storage->invalidateCaches();
      $extra = $view->buildRenderable('default', [$card->get('field_display_document_list')->target_id]);
    }

    if ($card->field_image_media && !$card->field_image_media->isEmpty()) {
      $img_media = Media::load($card->field_image_media[0]->getValue('target_id')['target_id']);
      $fid = $img_media->field_media_image->target_id;
      $file = File::load($fid);
      $styled_image_url = ImageStyle::load('max_650x650')->buildUrl($file->getFileUri());

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

    if ($card->field_icon && $card->field_icon->value) {
      $icon['name'] = str_replace("icon-fa-", "", $card->field_icon->value);
    }

    $set[] = [
      'style' => $sub_variants['card'],
      'title' => $title,
      'brow' => $brow,
      'body' => $card->field_text->view('default'),
      'extra_content' => $extra,
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

  return $set;
}
