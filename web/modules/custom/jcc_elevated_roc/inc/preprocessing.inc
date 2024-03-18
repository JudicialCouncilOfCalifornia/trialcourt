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

    // Get all the processed document content items in the document, and build
    // a list of all the level 1, 2, and 3 items. Set level 3 items as a link to
    // the processed_content entity page.
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
              $url = Url::fromRoute('entity.processed_content.canonical', ['processed_content' => $content->id()]);
              $items[] = [
                '#markup' => Link::fromTextAndUrl($content->get('field_title')->getValue()[0]['value'], $url)->toString(),
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

    $link_back_to_index = Link::fromTextAndUrl(t('Back to Rules of Court'), Url::fromUserInput('/cms/rules/index'))->toString();
    $back_to_top_url = Url::fromRoute('<current>')->setOption('fragment', 'document_top');

    //
    // Generate the upper links for the document output.
    $variables['content']['upper_links'] = [
      '#theme' => 'item_list',
      '#title' => '',
      '#items' => [
        'back_to_rules_index' => $link_back_to_index,
        'back_to_top' => [
          '#markup' => Link::fromTextAndUrl('Back to top', $back_to_top_url)->toString(),
          '#wrapper_attributes' => ['class' => 'item__align-right'],
        ],
        'search' => 'Search this page',
      ],
      '#type' => 'ul',
      '#attributes' => ['class' => 'roc-document-toc'],
      '#cache' => [
        'tags' => $node->getCacheTags(),
      ],
    ];

    //
    // Generate the lower links for the document output.
    $variables['content']['lower_links'] = [
      '#theme' => 'item_list',
      '#title' => '',
      '#items' => [
        'back_to_rules_index' => $link_back_to_index,
        'back_to_top' => [
          '#markup' => Link::fromTextAndUrl('Back to top', $back_to_top_url)->toString(),
          '#wrapper_attributes' => ['class' => 'item__align-right'],
        ],
      ],
      '#type' => 'ul',
      '#attributes' => ['class' => 'roc-document-toc'],
      '#cache' => [
        'tags' => $node->getCacheTags(),
      ],
    ];
  }
}

/**
 * Implements template_preprocess_HOOK().
 */
function jcc_elevated_roc_preprocess_eck_entity(&$variables): void {
  if ($variables['bundle'] == 'processed_content') {
    $entity = $variables['eck_entity'];

    //
    // Get the parent entity of the Processed content section.
    $parent_node = $entity->get('field_parent_node')->referencedEntities();
    $parent_node = $parent_node[0] ?? FALSE;

    //
    // Get the content of the subsection ready for display.
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

    //
    // Build out our links.
    if ($parent_node) {
      $options = [
        'attributes' => [
          'class' => ['roc-document-list__item__link'],
          'title' => t('View the table of contents for "@name"', ['@name' => $parent_node->label()]),
        ],
      ];

      $link_prev = Link::fromTextAndUrl(t('Previous'), $parent_node->toUrl('canonical', $options))->toString();
      $link_back_to_toc = Link::fromTextAndUrl(t('Back to Rule Table of Contents'), $parent_node->toUrl('canonical', $options))->toString();
      $link_next = Link::fromTextAndUrl(t('Next'), $parent_node->toUrl('canonical', $options))->toString();
      $back_to_top_url = Url::fromRoute('<current>')->setOption('fragment', 'document_top');

      //
      // Generate the upper links for the document output.
      $variables['content']['upper_links'] = [
        '#theme' => 'item_list',
        '#title' => '',
        '#items' => [
          'previous' => $link_prev,
          'back_to_rule_toc' => $link_back_to_toc,
          'next' => $link_next,
          'back_to_top' => [
            '#markup' => Link::fromTextAndUrl('Back to top', $back_to_top_url)->toString(),
            '#wrapper_attributes' => ['class' => 'item__align-right'],
          ],
          'search' => 'Search this page',
        ],
        '#type' => 'ul',
        '#attributes' => ['class' => 'roc-document-toc'],
        '#cache' => [
          // 'tags' => $entity->getCacheTags(),
        ],
      ];

      //
      // Generate the lower links for the document output.
      $variables['content']['lower_links'] = [
        '#theme' => 'item_list',
        '#title' => '',
        '#items' => [
          'previous' => $link_prev,
          'back_to_rule_toc' => $link_back_to_toc,
          'next' => $link_next,
          'back_to_top' => [
            '#markup' => Link::fromTextAndUrl('Back to top', $back_to_top_url)->toString(),
            '#wrapper_attributes' => ['class' => 'item__align-right'],
          ],
        ],
        '#type' => 'ul',
        '#attributes' => ['class' => 'roc-document-toc'],
        '#cache' => [
          // 'tags' => $entity->getCacheTags(),
        ],
      ];
    }

  }
}
