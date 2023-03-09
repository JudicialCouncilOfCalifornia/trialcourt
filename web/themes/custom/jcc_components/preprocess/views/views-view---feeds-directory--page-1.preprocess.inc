<?php

/**
 * @file
 * Preprocessors for views.
 */

use Drupal\taxonomy\Entity\Term;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_views_view__feeds_directory__page_1(&$variables) {
  if (empty($variables['rows'])) {
    return;
  }
  $feeds_types = [];
  $feeds_topics = [];
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
      'url' => '/cats/' . $tid,
    ];

    switch ($vid) {
      case 'news_type':
        array_push($feeds_topics, $feed_details);
        break;

      default:
        array_push($feeds_types, $feed_details);
    }
  }

  $feeds_types = $feeds_topics;
  foreach ($feeds_types as $key => $type) {
    if ($type['name'] != 'News Release') {
      unset($feeds_types[$key]);
    }
  }
  $feeds_types = array_values($feeds_types);
  $feeds_types[0]['name'] = 'News & Events';
  $feeds_types[0]['url'] = '/news-events';
  $feeds = [
    [
      'feed_type' => 'News Feed',
      'feed_items' => $feeds_types,
    ],
    [
      'feed_type' => 'Categories',
      'feed_items' => $feeds_topics,
    ],
  ];

  $variables['feeds'] = $feeds;
}