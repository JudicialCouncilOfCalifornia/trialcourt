<?php

/**
 * @file
 */

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * @file
 * A file for node preprocessing.
 */

/**
 * Implements theme_preprocess_node().
 */
function jcc_elevated_roc_preprocess_node(&$variables): void {
  $node = $variables['node'];
  $pc_manager = Drupal::entityTypeManager()->getStorage('processed_content');
  $roc_rule_service = \Drupal::service('jcc_elevated_roc.rule.service');

  // Setup "Table of contents" listing on a document node.
  if ($node->getType() == 'document') {

    $processed_content = $node->get('field_processed_document_content')->getValue();
    if (!empty($processed_content)) {
      $items = [];
      foreach ($node->get('field_processed_document_content')->getValue() as $item) {
        $content = $pc_manager->load($item['target_id']);
        if ($content) {
          $level = $content->get('field_section_level')->first() ? $content->get('field_section_level')->getValue()[0]['value'] : FALSE;
          $levels = ['1', '2', '3'];
          if (isset($level) && in_array($level, $levels)) {
            if ($level == '3') {
              $base_path = $roc_rule_service->getRocBasePath();
              $parent_id = $content->get('field_parent_node_document_id')->getValue()[0]['value'];
              $section_id = $content->get('field_parent_section')->getValue()[0]['value'];
              $path = implode('/', [$base_path, $parent_id, $section_id]);
              $items[] = [
                '#markup' => Link::fromTextAndUrl($content->get('field_title')->getValue()[0]['value'], Url::fromUserInput($path))->toString(),
                '#wrapper_attributes' => ['class' => 'TOClevel3'],
              ];
            }
            else {
              $items[] = [
                '#markup' => $content->get('field_title')->getValue()[0]['value'],
                '#wrapper_attributes' => ['class' => 'TOClevel' . $level],
              ];
            }
          }
        }
      }

      $variables['content']['table_of_contents'] = [
        '#theme' => 'item_list',
        '#title' => array_shift($items),
        '#items' => $items,
        '#type' => 'ul',
        '#attributes' => ['class' => 'roc-document-toc'],
        '#cache' => [
          'tags' => $node->getCacheTags(),
        ],
      ];
    }
  }
}

/**
 * Implements template_preprocess_HOOK().
 */
function jcc_elevated_roc_preprocess_eck_entity(&$variables): void {
  if ($variables['bundle'] == 'processed_content') {
    if (isset($variables['content']['field_content'][0])) {
      $text = json_decode($variables['content']['field_content'][0]['#text']);
      $text_item = '';

      foreach ($text as $delta => $item) {
        if ($delta != 0) {
          $text_item .= "<p class='$item->style'>" . $item->content . '</p>';
        }
      }
      $variables['content']['field_content'][0]['#text'] = $text_item;
    }
  }
}
