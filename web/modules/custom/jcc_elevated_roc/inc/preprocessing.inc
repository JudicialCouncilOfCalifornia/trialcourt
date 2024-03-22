<?php

/**
 * @file
 */

use Drupal\Core\Cache\CacheBackendInterface;
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
        'in_page_search' => jcc_elevated_roc_in_page_search_embed(),
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
      if (is_array($text)) {
        foreach ($text as $delta => $item) {
          if ($delta != 0) {
            $text_item .= "<p class='$item->style'>" . $item->content . '</p>';
          }
        }
      }
      $variables['content']['field_content'][0]['#text'] = $text_item;
    }

    //
    // Build out our links.
    if ($parent_node) {
      $prev_next_list = jcc_elevated_roc_get_prev_next_list($entity, $parent_node);

      //
      // Generate the upper and lower links for the document output.
      $settings = [
        '#theme' => 'item_list',
        '#title' => '',
        '#items' => [],
        '#type' => 'ul',
        '#attributes' => ['class' => 'roc-document-toc'],
        '#cache' => [
          'tags' => [
            'processed_content:' . $entity->id(),
            'node:' . $parent_node->id(),
          ],
        ],
      ];
      $variables['content']['upper_links'] = $settings;
      $variables['content']['lower_links'] = $settings;

      //
      // Previous link.
      if (!empty($prev_next_list['previous'])) {
        $prev_options = ['attributes' => ['class' => ['roc-document-list__item__link']]];
        $link_prev = Link::fromTextAndUrl(t('Previous'), $prev_next_list['previous']->toUrl('canonical', $prev_options))->toString();
        $variables['content']['upper_links']['#items']['link_prev'] = $link_prev;
        $variables['content']['lower_links']['#items']['link_prev'] = $link_prev;
      }

      //
      // Add the "back to rules TOC" link.
      $options = [
        'attributes' => [
          'class' => ['roc-document-list__item__link'],
          'title' => t('View the table of contents for "@name"', ['@name' => $parent_node->label()]),
        ],
      ];
      $link_back_to_toc = Link::fromTextAndUrl(t('Back to Rule Table of Contents'), $parent_node->toUrl('canonical', $options))->toString();
      $variables['content']['upper_links']['#items']['rule_toc'] = $link_back_to_toc;
      $variables['content']['lower_links']['#items']['rule_toc'] = $link_back_to_toc;

      //
      // Next link.
      if (!empty($prev_next_list['next'])) {
        $next_options = ['attributes' => ['class' => ['roc-document-list__item__link']]];
        $link_next = Link::fromTextAndUrl(t('Next'), $prev_next_list['next']->toUrl('canonical', $next_options))->toString();
        $variables['content']['upper_links']['#items']['link_next'] = $link_next;
        $variables['content']['lower_links']['#items']['link_next'] = $link_next;
      }

      //
      // Add the back to top link.
      $back_to_top_url = Url::fromRoute('<current>')->setOption('fragment', 'document_top');
      $back_to_top_link = [
        '#markup' => Link::fromTextAndUrl('Back to top', $back_to_top_url)->toString(),
        '#wrapper_attributes' => ['class' => 'item__align-right'],
      ];
      $variables['content']['upper_links']['#items']['back_to_top'] = $back_to_top_link;
      $variables['content']['lower_links']['#items']['back_to_top'] = $back_to_top_link;

      //
      // Add the "Search this page" embedded link and form.
      $variables['content']['upper_links']['#items']['in_page_search'] = jcc_elevated_roc_in_page_search_embed();
    }

  }
}

/**
 * Get an array of all current subsection prev/next items.
 */
function jcc_elevated_roc_get_prev_next_list($pc_entity, $document_node) {
  $doc_nid = $document_node->id();
  $active_pc_id = $pc_entity->id();
  $cid = 'jcc_elevated_roc:prev_next__doc' . $doc_nid . '__section' . $active_pc_id;

  if ($cache = \Drupal::cache()->get($cid)) {
    $data = $cache->data;
  }
  else {
    $items = jcc_elevated_roc_get_ordered_document_sections($document_node);
    $index = array_search($active_pc_id, $items['keyed_items']);
    $pc_items = $items['pc_items'];
    $previous_item = is_numeric($index) && isset($pc_items[$index - 1]) ? $pc_items[$index - 1] : FALSE;
    $next_item = is_numeric($index) && isset($pc_items[$index + 1]) ? $pc_items[$index + 1] : FALSE;

    $data = [
      'previous' => $previous_item,
      'next' => $next_item,
    ];

    $tags = [
      'processed_content:' . $active_pc_id,
      'node:' . $doc_nid,
    ];
    \Drupal::cache()->set($cid, $data, CacheBackendInterface::CACHE_PERMANENT, $tags);
  }

  return $data;
}

/**
 * Stores an array of subsections (processed_content) items for each document.
 */
function jcc_elevated_roc_get_ordered_document_sections($document_node) {
  $doc_nid = $document_node->id();
  $cid = 'jcc_elevated_roc:prev_next__doc' . $doc_nid;
  if ($cache = \Drupal::cache()->get($cid)) {
    $data = $cache->data;
  }
  else {
    $pc_items = $document_node->get('field_processed_document_content')->referencedEntities();
    $items = [];
    foreach ($pc_items as $delta => $item) {
      if ($item->get('field_section_level')->value == '3') {
        $items[] = $item->id();
      }
      else {
        unset($pc_items[$delta]);
      }
    }
    $pc_items = array_values($pc_items);
    $data = [
      'pc_items' => $pc_items,
      'keyed_items' => $items,
    ];

    $tags = ['node:' . $doc_nid];
    \Drupal::cache()->set($cid, $data, CacheBackendInterface::CACHE_PERMANENT, $tags);
  }

  return $data;
}

/**
 *
 */
function jcc_elevated_roc_in_page_search_embed() {

  $build['form'] = [
    '#type' => 'form',
    '#attributes' => ['class' => ['cluster']],
  ];

  $build['form']['in_page_search_trigger'] = [
    '#type' => 'html_tag',
    '#tag' => 'button',
    '#value' => t('Search'),
  ];

  $build['form']['in_page_field'] = [
    '#type' => 'textfield',
    '#size' => 30,
    '#attributes' => [
      'aria-label' => t('Search this page'),
      'placeholder' => t('Search this page'),
      'id' => 'keywords',
      'name' => 'keywords',
      'spellcheck' => 'false',
      'aria-hidden' => 'true',
      'tabindex' => '-1',
    ],
  ];

  $build['form']['info'] = [
    '#type' => 'container',
    '#attributes' => ['class' => ['results-info']],
  ];

  $build['form']['info']['index'] = [
    '#type' => 'container',
    '#attributes' => ['class' => ['results-index']],
  ];

  $build['form']['info']['nav'] = [
    '#type' => 'container',
    '#attributes' => ['class' => ['results-nav']],
  ];

  $build['form']['info']['nav']['prev'] = [
    '#type' => 'html_tag',
    '#tag' => 'a',
    '#value' => 'Prev',
    '#attributes' => [
      'class' => ['results-index-prev'],
      'aria-label' => t('Previous match'),
      'href' => '#',
    ],
  ];

  $build['form']['info']['nav']['next'] = [
    '#type' => 'html_tag',
    '#tag' => 'a',
    '#value' => 'Next',
    '#attributes' => [
      'class' => ['results-index-next'],
      'aria-label' => t('Next match'),
      'href' => '#',
    ],
  ];

  return $build;
}
