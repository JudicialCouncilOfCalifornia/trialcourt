<?php

/**
 * @file
 * Paragraph preprocessors.
 */

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\views\ViewEntityInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_elevated_preprocess_paragraph(&$variables) {
  $paragraph = $variables['paragraph'];
  $function = "jcc_elevated_paragraph_" . $paragraph->bundle();
  if (function_exists($function)) {
    $function($variables, $paragraph);
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function jcc_elevated_theme_suggestions_paragraph_alter(&$suggestions, $variables) {
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
 * Helper function for views_reference paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The paragraph.
 */
function jcc_elevated_paragraph_views_reference(array &$variables, ParagraphInterface $paragraph) {
  // In some cases we need to pass the views results to the paragraph.
  if (!empty($paragraph->field_views_reference)) {
    $view = $paragraph->field_views_reference->referencedEntities()[0];
    $reference = $paragraph->field_views_reference->first();
    $target_id = $reference->target_id;
    $display_id = $reference->display_id;
    $function = "jcc_elevated_paragraph_views_reference_{$target_id}_{$display_id}";
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
function jcc_elevated_paragraph_views_reference_news_sticky_list(array &$variables, ViewEntityInterface $view) {
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
  $options = ['absolute' => TRUE];
  $variables['news_url'] = Url::fromUserInput($path, $options);
  $variables['#cache']['tags'][] = 'node_list:news';
}

/**
 * Helper function for cards paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The paragraph.
 */
function jcc_elevated_paragraph_card(array &$variables, ParagraphInterface $paragraph) {
  if ($paragraph->get('field_media')) {
    $card_media = $paragraph->get('field_media');
    $card_icon = $paragraph->get('field_icon');
    if ($card_media && $card_media->entity) {
      $build = \Drupal::entityTypeManager()->getViewBuilder('media')->view($card_media->entity);
      $variables['card_media'] = $build;
    }
    if ($card_icon && $card_icon->value) {
      $variables['card_icon'] = str_replace(
        ['icon-', 'dark-', 'white-'],
        ['', '', ''],
        $card_icon->value);
    }
  }

  // Disallows links in text if clickable striped card.
  $variables['filtered_text'] = NULL;
  $parent = $paragraph->getParentEntity();
  if ($parent && $parent->hasField('field_sub_variant')) {
    $cards_sub_variant = $parent->get('field_sub_variant')->value;
    $cards_sub_variant = substr($cards_sub_variant, strpos($cards_sub_variant, ':'));
    $cards_sub_variant = preg_replace("/[^a-zA-Z0-9]+/", "", $cards_sub_variant);
    if ($cards_sub_variant == 'striped' && $paragraph->field_entire_card_clickable->value) {
      // Allowed tags in minimal editor except <a>.
      $allowed_tags = '<p><em><strong><u><s><sup><sub><ol><li><ul>';
      $text = $paragraph->get('field_text')->value;
      $text = strip_tags($text, $allowed_tags);
      $variables['filtered_text'] = Markup::create($text);
    }
  }

}

/**
 * Helper function for accordion paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 */
function jcc_elevated_paragraph_accordion(array &$variables) {
  $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  $variables['accordion_items'] = [];

  // Build the array of accordion items.
  if ($variables['paragraph']->hasField('field_components')) {
    $items = $variables['paragraph']->get('field_components')->referencedEntities();

    foreach ($items as $item) {
      $item = \Drupal::service('entity.repository')->getTranslationFromContext($item, $langcode);
      $variables['accordion_items'][] = [
        'label' => $item->hasField('field_heading') ? $item->get('field_heading')->first()->value : '',
        'content' => $item->get('field_text')->view('default'),
      ];
    }
  }
}

/**
 * Helper function for steps paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 */
function jcc_elevated_paragraph_steps(array &$variables) {
  $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  $variables['steps'] = [];

  // Build the array of step items.
  if ($variables['paragraph']->hasField('field_components')) {
    $items = $variables['paragraph']->get('field_components')->referencedEntities();

    foreach ($items as $item) {
      $item = \Drupal::service('entity.repository')->getTranslationFromContext($item, $langcode);
      $variables['steps'][] = [
        'label' => $item->hasField('field_heading') ? $item->get('field_heading')->first()->value : '',
        'content' => $item->get('field_text')->view('default'),
      ];
    }
  }
}

/**
 * Helper function for alert paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 */
function jcc_elevated_paragraph_alert(array &$variables) {
  $variables['type'] = NULL;

  // Get the alert type from the variant field.
  if ($variables['paragraph']->hasField('field_variant')) {
    $items = $variables['paragraph']->get('field_variant')->getValue();
    $variant = json_decode($items[0]['value']);
    if ($variant->alert) {
      $variables['type'] = $variant->alert == 'success' ? 'status' : $variant->alert;
    }
  }
}

/**
 * Helper function for hero_hangover paragraph.
 *
 * @param array $variables
 *   The paragraph variables.
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The paragraph.
 */
function jcc_elevated_paragraph_hero_hangover(array &$variables, ParagraphInterface $paragraph) {
  if ($paragraph->get('field_media')) {
    $card_media = $paragraph->get('field_media');
    if ($card_media && $card_media->entity) {
      $build = \Drupal::entityTypeManager()
        ->getViewBuilder('media')
        ->view($card_media->entity);
      $variables['card_media'] = $build;
    }
  }
}

/**
 * Helper function for alert paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 */
function jcc_elevated_paragraph_action_list(array &$variables) {
  $action_items = [];
  $paragraph = $variables['paragraph']->get('field_components')->getValue();
  foreach ($paragraph as $key => $element) {
    $action_item = Paragraph::load($element['target_id']);
    $link_element = $action_item->get('field_link')->getValue();
    $action_items[$key]['label'] = $link_element[0]['title'];
    $action_items[$key]['button_data']['href'] = $link_element[0]['uri'];
    $action_items[$key]['button_data']['label'] = 'Learn More';
  }
  $variables['action_items'] = $action_items;
}
