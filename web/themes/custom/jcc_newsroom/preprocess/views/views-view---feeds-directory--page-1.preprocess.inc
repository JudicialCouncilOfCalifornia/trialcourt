<?php

/**
 * @file
 * Preprocessors for views.
 */

use Drupal\taxonomy\Entity\Term;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_newsroom_preprocess_views_view__feeds_directory__page_1(&$variables) {
  if (empty($variables['rows'])) {
    return;
  }

  $feeds_types = [];
  $feeds_topics = [];
  $feeds_tags = [];

  foreach ($variables['rows'][0]['#rows'] as $row) {
    $term = $row['#row']->_entity;
    $term_name = $term->name->value;
    $tid = $term->tid->value;
    $vid = Term::load($tid)->bundle();
    $feed_details = [
      'icon' => [
        'name' => 'rss2',
        'title' => 'rss2',
      ],
      'name' => $term_name,
      'url' => '/cats/' . $term_name,
    ];

    switch ($vid) {
      case 'topics':
        array_push($feeds_topics, $feed_details);
        break;

      case 'tags':
        array_push($feeds_tags, $feed_details);
        break;

      default:
        if ($term_name === 'Feature' or $term_name === 'News Release') {
          array_push($feeds_types, $feed_details);
        }
    }
  }

  $feeds = [
    [
      'feed_type' => 'popular',
      'feed_items' => $feeds_types,
    ],
    [
      'feed_type' => 'topics',
      'feed_items' => $feeds_topics,
    ],
    [
      'feed_type' => 'tags',
      'feed_items' => $feeds_tags,
    ],
  ];

  $variables['feeds'] = $feeds;
}
