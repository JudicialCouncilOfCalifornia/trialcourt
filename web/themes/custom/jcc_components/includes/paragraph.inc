<?php

/**
 * @file
 * Preprocess and functions for media.
 */

use Drupal\paragraphs\ParagraphInterface;
use Drupal\views\ViewEntityInterface;

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
 * Implements hook_theme_suggestions_alter().
 */
function jcc_components_theme_suggestions_alter(&$suggestions, $variables, $hook) {
  if ($hook == 'paragraph') {
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
  foreach ($cards as $card) {
    $title = !empty($card->field_heading) ? $card->field_heading->first()->value : '';

    if (!empty($card->field_link)) {
      // Link field formatter double encodes urls which becomes a problem when
      // we allow file urls that have special characters like spaces in the
      // filename. Decode the uri before allowing it to render. It will be re-
      // encoded by the core link field formatter.
      // There's also a decoder in the LinkitWidget to prevent double encoding
      // paths on successive saves.
      // Consider a core patch to LinkFormatter.
      $link_value = $card->field_link->first()->getValue();
      $link_value['uri'] = rawurldecode($link_value['uri']);
      $card->field_link->setValue($link_value);
      $link = $card->field_link->view('default');
    }

    $variables['cards'][] = [
      'style' => $sub_variants['card'],
      'title' => $title,
      'brow' => $card->field_brow->view('default'),
      'body' => $card->field_text->view('default'),
      'link' => [
        'url' => !empty($link[0]) ? $link[0]['#url'] : NULL,
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
}

/**
 * Helper function for views_reference paragrpahs of type location.
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
      $variables['map_list_attachment'][] = [
        'title' => $row['#node']->getTitle(),
        'address' => $row['#node']->field_address->view('default'),
      ];
    }
  }
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
