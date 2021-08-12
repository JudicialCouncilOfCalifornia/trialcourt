<?php

/**
 * @file
 * Preprocessors for views.
 */

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_newsroom_preprocess_views_view_row_rss(&$variables) {
  // Extending RSS content
  $item = $variables['row'];
  $node = Node::load($item->nid);

  if (isset($node)) {
    // Content type.
    $variables['content_type'] = $node->type->entity->label();
    // "Sub-type" tag.
    $tid = '';
    switch ($node->type->entity->label()) {
      case 'News':
        $tid = $node->get('field_news_type')->target_id;
        break;
      case 'Event':
        $tid = $node->get('field_event_type')->target_id;
        break;
      default:
        break;
    }
    $term = Term::load($tid);
    $variables['type'] = $term->getName();
    // Author, not page creator.
    $variables['author'] = Node::load($node->get('field_contact')->target_id);
    // Body and teaser
    $variables['body'] = $node->body->view('full');
    $variables['teaser'] = $node->body->view('teaser');
    // Attached images not inline with body.
    if (!empty($node->get('field_images'))) {
      $first_image = _jcc_get_first_image($node);
      $variables['image'] = $first_image['url'];
    }
    // Publish status.
    $variables['pubStatus'] = $node->get('status')->getString();
  }
}
