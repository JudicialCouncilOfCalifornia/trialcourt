<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

use Drupal\Core\Render\Markup;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_block__views_block__news_block_3(&$variables) {
  $header_title = empty($variables['elements']['#configuration']['views_label']) ? $variables['elements']['content']['#title']['#markup'] : $variables['elements']['#configuration']['views_label'];
  $tiles = [];

  if (isset($variables['elements']['content']['#view'])) {
    $rows = $variables['elements']['content']['#view']->result;
    foreach ($rows as $row) {
      $date = date("M j, Y", $row->_entity->created->value);
      $title = $row->_entity->title->value;
      $body = '';
      if ($row->_entity->body->summary != NULL) {
        $body = $row->_entity->body->summary;
      }
      elseif ($row->_entity->body->value != NULL) {
        $body = substr(strip_tags($row->_entity->body->value), 0, 200);
      }
      else {
        if (($row->_entity->field_attachments != NULL) && ($row->_entity->field_attachments->entity != NULL)) {
          $body = $row->_entity->field_attachments->entity->getName();
        }
      }

      // Get all possible categories as a single listing per node.
      $categories = _jcc_list_faceted_terms($row->_entity,
        ['field_topics', 'field_tags', 'field_division', 'field_subject_matter']);

      $excerpt = Markup::create('<div class="publish_date">' . $date . '</div>' . $body . '<div class="jcc-tags">' . $categories . '</div>');
      $url = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $row->_entity->nid->value);

      $img_elem = '';
      $first_image = _jcc_get_first_image($row->_entity);
      if ($first_image) {
        $img_elem = Markup::create('<a href="' . $url . '" data-colorbox-gallery="gallery-beforeafter"><img src="' . $first_image['url'] . '" alt="' . $first_image['alt'] . '"></a>');
      }

      $moduleHandler = Drupal::service('module_handler');
      $user = Drupal::currentUser();
      $placeholder = FALSE;
      if ($moduleHandler->moduleExists('contextual') && $user->hasPermission('access contextual links')) {
        $contextual_links = [
          'node' => [
            'route_parameters' => [
              'node' => $row->_entity->id(),
            ],
          ],
        ];
        $placeholder = [
          '#type' => 'contextual_links_placeholder',
          '#id' => _contextual_links_to_id($contextual_links),
        ];
      }

      $recent_news_tile = [
        'card' => [
          'title' => $title,
          'excerpt' => $excerpt,
          'url' => $url,
        ],
        'img' => $img_elem ?? '',
        'contextual_links' => $placeholder,
      ];
      array_push($tiles, $recent_news_tile);
    }

    if ($variables['elements']['content']['#view']->display_handler->display['display_options']['use_more'] == TRUE) {
      $button_text = $variables['elements']['content']['#view']->display_handler->display['display_options']['use_more_text'];
      $button_url = '/news';

      $button = [
        'text' => $button_text,
        'url' => $button_url,
        'style' => 'primary',
      ];
    }
    else {
      $button = [];
    }

    $variables['news_stream'] = [
      'headergroup' => [
        'title' => htmlspecialchars_decode($header_title),
      ],
      'tiles' => $tiles,
      'button' => $button,
    ];
  }
  else {
    $variables['news_stream'] = [
      'headergroup' => [
        'title' => htmlspecialchars_decode($header_title),
      ],
    ];
  }
}
