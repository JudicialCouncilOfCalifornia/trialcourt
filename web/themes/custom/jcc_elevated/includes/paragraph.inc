<?php

/**
 * @file
 * Paragraph preprocessors.
 */

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\views\ViewEntityInterface;

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

  // Allow for parent bundle type in template suggestion.
  $parent = $paragraph->getParentEntity();
  if ($parent && $parent->bundle()) {
    $suggestions[] = 'paragraph__' . $paragraph->bundle() . '__' . $parent->bundle();
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
function jcc_elevated_paragraph_views_reference(array &$variables, ParagraphInterface $paragraph): void {
  // In some cases we need to pass the views results to the paragraph.
  $ref_override = [];

  if (!empty($paragraph->field_views_reference)) {
    $view = $paragraph->field_views_reference->referencedEntities()[0];
    $reference = $paragraph->field_views_reference->first();

    $value = $reference->getValue();
    $ref_override = $value['data'] ? unserialize($value['data']) : []; // phpcs:ignore

    $target_id = $reference->target_id;
    $display_id = $reference->display_id;
    $function = "jcc_elevated_paragraph_views_reference_{$target_id}_{$display_id}";
  }

  $variables['more']['use_more_text'] = !empty($ref_override['use_more_text']) ? $ref_override['use_more_text'] : FALSE;
  $variables['more']['link_display'] = !empty($ref_override['link_display']) ? Url::fromUri($ref_override['link_display']) : FALSE;

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
  $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  $executable = $view ? $view->getExecutable() : NULL;

  // Start with the sticky_list display.
  $sticky_list = $executable ? $executable->setDisplay('sticky_list') : FALSE;
  $display = $executable->executeDisplay();

  if ($sticky_list) {
    $variables['sticky_list'] = !empty($display['#rows']) ? $display['#rows'][0]['#rows'] : [];
    foreach ($variables['sticky_list'] as $sticky_item) {
      if (!empty($sticky_item['#node']->get('field_components')->getValue()[0]['target_id'])) {
        $paragraph_target_id = $sticky_item['#node']->get('field_components')->getValue()[0]['target_id'];
      }
      if ($sticky_item['#node']->get('body')->isEmpty()) {
        $paragraph = Paragraph::load($paragraph_target_id);
        // Render the text field the same as if it was being rendered in the
        // full default view mode. This helps apply proper text/html filtering.
        $item = \Drupal::service('entity.repository')->getTranslationFromContext($paragraph, $langcode);
        $content = $item->get('field_text')->view('default');
      }
      else {
        $content = $sticky_item['#node']->get('body')->view('teaser');
      }
      $variables['excerpts'][] = $content;
    }
  }

  // Also get the More News path. Should be a node with path /news but fallback
  // to view url if it exists.
  $view_url = !empty($executable->getUrl()) ? $executable->getUrl()->toString() : '';
  $path = \Drupal::service('path.validator')->isValid('/news') ? '/news' : $view_url;
  $options = ['absolute' => TRUE];

  // Apply a section query to this link, if the sections module is enabled and
  // the current page has a section applied.
  if (\Drupal::service('module_handler')->moduleExists('jcc_elevated_sections')) {
    if ($sid = jcc_get_current_page_section()) {
      $options['query'] = ['jcc_section' => $sid];
    }
  }

  $variables['news_url'] = Url::fromUserInput($path, $options);
  $variables['#cache']['tags'][] = 'node_list:news';
}

/**
 * Helper function for card paragraphs.
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
      $caption = $card_media->entity->field_media_caption && $card_media->entity->field_media_caption->value ? $card_media->entity->field_media_caption->value : NULL;
      $variables['media'] = $build;
      if ($paragraph->get('field_show_media_caption')->value) {
        $variables['media_caption'] = [
          'show' => $paragraph->get('field_show_media_caption')->value,
          'text' => $caption ? Markup::create($caption) : NULL,
        ];
      }
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
      if ($text !== NULL) {
        $text = strip_tags($text, $allowed_tags);
      }
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

  if (\Drupal::request()->query->get('acc')) {
    $variables['open_on_load'] = \Drupal::request()->query->get('acc');
  }

  // Build the array of accordion items.
  if ($variables['paragraph']->hasField('field_components')) {
    $items = $variables['paragraph']->get('field_components')->referencedEntities();

    foreach ($items as $item) {
      $item = \Drupal::service('entity.repository')->getTranslationFromContext($item, $langcode);
      $variables['accordion_items'][] = [
        'label' => $item->hasField('field_heading') ? $item->get('field_heading')->first()->value : '',
        'content' => $item->get('field_text')->view('default'),
        'id' => $item->get('field_id')->value,
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
function jcc_elevated_paragraph_steps(array &$variables): void {
  $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  $variables['steps'] = [];

  // Build the array of step items.
  if ($variables['paragraph']->hasField('field_components')) {
    $items = $variables['paragraph']->get('field_components')->referencedEntities();

    foreach ($items as $item) {
      $item = \Drupal::service('entity.repository')->getTranslationFromContext($item, $langcode);
      $variables['steps'][] = [
        'index' => $item->hasField('field_custom_index') && $item->get('field_custom_index')->first() ? $item->get('field_custom_index')->value : FALSE,
        'label' => $item->hasField('field_heading') && $item->get('field_heading')->first() ? $item->get('field_heading')->first()->value : FALSE,
        'content' => $item->hasField('field_text') && $item->get('field_text')->first() ? $item->get('field_text')->view('default') : FALSE,
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
    if (isset($variant->alert)) {
      $variables['type'] = $variant->alert == 'success' ? 'status' : $variant->alert;
    }
  }
}

/**
 * Helper function for hero paragraph.
 *
 * @param array $variables
 *   The paragraph variables.
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The paragraph.
 */
function jcc_elevated_paragraph_hero(array &$variables, ParagraphInterface $paragraph) {
  if (!empty($paragraph->get('field_components'))) {
    foreach ($paragraph->get('field_components') as $card) {
      $icon = $card->entity->field_icon->value;
      if ($icon) {
        $icon = str_replace(['icon-', 'dark-', 'white-'], ['', '', ''], $icon);
      }
      $variables['cards'][] = [
        'variant' => 'striped-aligned',
        'clickable_card' => TRUE,
        'icon_data' => [
          'icon' => $icon,
          'color' => FALSE,
          'icon_path' => ' ',
        ],
        'heading' => $card->entity->field_link->title,
        'button_data' => [
          'label' => 'Button',
          'href' => Url::fromUri($card->entity->field_link->uri)->toString(),
          'variant' => 'primary',
        ],
      ];
    }
  }
  if (!empty($paragraph->get('field_image_media')) && $paragraph->get('field_image_media')->entity != NULL) {
    $cover_image = $paragraph->get('field_image_media')->entity->field_media_image->entity->getFileUri();
    $image_url = ImageStyle::load('hero_banner')->buildUrl($cover_image);
    $variables['bckg_img_url'] = $image_url;
  }

  // Include view embed as lead content.
  if (isset($paragraph->field_granicus_view_link_id->value)) {
    $variables['#attached']['library'][] = 'jcc_storybook/Iframe';
    $lead = $paragraph->get('field_lead')->value ?? '';
    $granicus_view_id = $paragraph->field_granicus_view_link_id->value;
    $granicus_url = 'https://jcc.granicus.com/ViewPublisher.php?view_id=' . $granicus_view_id;
    $iframe_title = 'Granicus live cast';
    $link = '<div class="iframe"><iframe class="iframe--granicus-link" src="' . $granicus_url . '" title="' . $iframe_title . '"></iframe></div>';
    $custom = $lead . $link;
    $variables['custom'] = Markup::create($custom);
  }
}

/**
 * Helper function for hero paragraph.
 *
 * @param array $variables
 *   The paragraph variables.
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The paragraph.
 */
function jcc_elevated_paragraph_hero_banner(array &$variables, ParagraphInterface $paragraph) {
  if (!empty($paragraph->get('field_components'))) {
    foreach ($paragraph->get('field_components') as $card) {
      $icon = $card->entity->field_icon->value;
      if ($icon) {
        $icon = str_replace(['icon-', 'dark-', 'white-'], ['', '', ''], $icon);
      }
      $variables['cards'][] = [
        'variant' => 'striped-aligned',
        'clickable_card' => TRUE,
        'icon_data' => [
          'icon' => $icon,
          'color' => FALSE,
          'icon_path' => ' ',
        ],
        'heading' => $card->entity->field_link->title,
        'button_data' => [
          'label' => 'Button',
          'href' => Url::fromUri($card->entity->field_link->uri)->toString(),
          'variant' => 'primary',
        ],
      ];
    }
  }
}

/**
 * Helper function for section paragraph.
 *
 * @param array $variables
 *   The paragraph variables.
 * @param \Drupal\paragraphs\ParagraphInterface $paragraph
 *   The paragraph.
 */
function jcc_elevated_paragraph_section(array &$variables, ParagraphInterface $paragraph) {
  if ($paragraph->get('field_media')->target_id) {
    $media = $paragraph->get('field_media')->target_id ? Media::load($paragraph->get('field_media')->target_id) : FALSE;
    if ($media && $media->field_media_image) {
      $cover_image = $media->get('field_media_image')[0]->entity->getFileUri();
      $image_url = ImageStyle::load('container_100_percent')->buildUrl($cover_image);
      $variables['background_image_url'] = $image_url ?? NULL;
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

    $action_items[$key]['label'] = '';
    if ($action_item->hasField('field_heading')) {
      if ($heading_element = $action_item->get('field_heading')->getValue()) {
        $action_items[$key]['label'] = $heading_element[0]['value'];
      }
    }

    $action_items[$key]['description'] = '';
    if ($action_item->hasField('field_text')) {
      if (!empty($action_item->get('field_text')->getValue())) {
        $action_items[$key]['description'] = $action_item->field_text->view('default');
      }
    }

    if ($action_item->hasField('field_link')) {
      if ($link_element = $action_item->get('field_link')->getValue()) {
        $action_items[$key]['button_data']['href'] = $link_element[0]['uri'];
        $action_items[$key]['button_data']['label'] = $link_element[0]['title'] ?? t('Learn More');
      }
    }
  }
  $variables['action_items'] = $action_items;
}

/**
 * Helper function for attachment paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function jcc_elevated_paragraph_attachment(array &$variables): void {
  $media_manager = \Drupal::entityTypeManager()->getStorage('media');
  $formatter = \Drupal::service('date.formatter');
  $paragraph = $variables['paragraph'];
  $media_target = $paragraph->get('field_media')->first() ? $paragraph->get('field_media')->first()->getValue() : FALSE;
  $media = $media_target['target_id'] ? $media_manager->load($media_target['target_id']) : FALSE;

  if ($media) {
    $current = Drupal::time()->getCurrentTime();
    $offset = 2592000;

    // If last updated date of media item is within 30 days, and not a new item,
    // set update TRUE.
    $update = $current - $media->getChangedTime() <= $offset;
    $new = $current - $media->getCreatedTime() <= $offset;

    $variables['attachment_status'] = FALSE;
    if ($new) {
      $variables['attachment_status'] = [
        '#prefix' => '<em class="attachment__new-label">',
        '#markup' => t('New'),
        '#suffix' => '</em>',
      ];
    }

    if ($update and !$new) {
      $variables['attachment_status'] = [
        '#prefix' => '<em class="attachment__recent-updated-date">',
        '#markup' => t('Revised:') . ' ' . $formatter->format($media->getChangedTime(), 'm_d_y'),
        '#suffix' => '</em>',
      ];
    }

    // If new item, setup some classes.
    $variables['new'] = $new ? 'attachment__new' : '';
  }

}

/**
 * Helper function for tabs paragraphs.
 *
 * @param array $variables
 *   The paragraph variables.
 */
function jcc_elevated_paragraph_tabs(array &$variables) {
  $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  $variant = json_decode($variables['paragraph']->get('field_variant')->value, TRUE);
  $variables['variant'] = $variant['tabs'] ?: 'dafault';

  // Build the array of tabs.
  $variables['tabs'] = [];
  if ($variables['paragraph']->hasField('field_components')) {
    $items = $variables['paragraph']->get('field_components')->referencedEntities();

    foreach ($items as $item) {
      $item = \Drupal::service('entity.repository')->getTranslationFromContext($item, $langcode);
      $variables['tabs'][] = [
        'label' => $item->hasField('field_heading') ? $item->get('field_heading')->first()->value : '',
        'content' => $item->get('field_content')->view('default'),
        'id' => $item->get('field_id')->value,
      ];
    }
  }
}
