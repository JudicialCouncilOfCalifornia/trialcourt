<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\taxonomy\Entity\Term;

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

      // BEGIN - Get all possible categories as a single listing per node.
      $categories = '';
      $topics = $row->_entity->field_topics->getValue();
      $tags = $row->_entity->field_tags->getValue();
      $taxonomies = array_merge($topics, $tags);
      $taxonomies = new RecursiveIteratorIterator(new RecursiveArrayIterator($taxonomies));
      // Display categories as links.
      $links = [];
      $already_displayed = [];
      foreach ($taxonomies as $taxonomy_id) {
        // Get taxonomy object.
        $taxonomy_entity = Term::load($taxonomy_id);
        // Label.
        $taxonomy_label = $taxonomy_entity->getName();
        // If taxonomy repeated between metadata types, display term once.
        if (!in_array(strtolower($taxonomy_label), $already_displayed)) {
          // Url.
          $taxonomy_url = $taxonomy_entity->toUrl();
          // Build the links.
          $link = Link::fromTextAndUrl($taxonomy_label, $taxonomy_url);
          $link = $link->toRenderable();
          array_push($links, render($link));
          // Track terms already displayed for node.
          $already_displayed[] = strtolower($taxonomy_label);
        }
      }
      // Return link array as string.
      if (!empty($links)) {
        $categories = '<span class="usa-tag">' . implode('</span> <span class="usa-tag">', array_unique($links)) . '</span>';
      }
      // END.
      $excerpt = Markup::create('<div class="publish_date">' . $date . '</div>' . $body . '<div class="tags">' . $categories . '</div>');
      $url = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $row->_entity->nid->value);
      $img_src = isset($row->_entity->field_images->entity->field_media_image->entity->uri->value) ? file_create_url($row->_entity->field_images->entity->field_media_image->entity->uri->value) : "";
      $img_alt = isset($row->_entity->field_images->entity->field_media_image->alt) ? $row->_entity->field_images->entity->field_media_image->alt : "";

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
        'img' => [
          'src' => $img_src,
          'alt' => $img_alt,
          'renderer' => 'patternlab',
        ],
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
