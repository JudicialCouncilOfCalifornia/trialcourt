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
  // Extending RSS content.
  $item = $variables['row'];
  $node = Node::load($item->nid);

  if (isset($node)) {
    $content_type = $node->type->entity->label();
    $extend_rss = FALSE;
    $tid = '';
    switch ($content_type) {
      case 'News':
        $extend_rss = TRUE;
        $tid = $node->get('field_news_type')->target_id;
        break;

      case 'Event':
        $extend_rss = TRUE;
        $tid = $node->get('field_event_type')->target_id;
        break;

      default:
        break;
    }
    if (isset($extend_rss)) {
      // Content type.
      $variables['content_type'] = $content_type;
      // "Sub-type" tag.
      if (isset($tid)) {
        $term = Term::load($tid);
        $variables['type'] = $term->getName();
      }
      // Author, not page creator, when applicable such as news contact.
      if (isset($node->field_contact) && !empty($node->get('field_contact')->target_id)) {
        $author_id = $node->get('field_contact')->target_id;
        $variables['author'] = Node::load($author_id);
      }
      // Tagline.
      $variables['tagline'] = $node->get('field_tagline')->value;
      // Date range when applicable such an event.
      if (isset($node->field_date_range) && !empty($node->get('field_date_range'))) {
        $variables['startDate'] = $node->get('field_date_range')->value;
        $variables['endDate'] = $node->get('field_date_range')->end_value;
      }
      // Body and teaser.
      $variables['body'] = $node->body->view('full');
      $variables['teaser'] = $node->body->view('teaser');
      // Attached images not inline with body.
      if (isset($node->field_images) && !empty($node->get('field_images'))) {
        $first_image = _jcc_get_first_image($node);
        $variables['image'] = $first_image['url'];
      }
      // Publish status.
      $variables['pubStatus'] = $node->get('status')->getString();
    }
  }
}
