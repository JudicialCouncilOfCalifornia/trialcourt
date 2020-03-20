<?php

namespace Drupal\jcc_book_navigation\Plugin\Block;

use Drupal\book\Plugin\Block\BookNavigationBlock;

/**
 * Provides a 'JccBookNavigationBlock' block.
 *
 * @Block(
 *  id = "jcc_book_navigation_block",
 *  admin_label = @Translation("JCC Book Navigation Block"),
 *   category = @Translation("Menus")
 * )
 */
class JccBookNavigationBlock extends BookNavigationBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_bid = 0;
    if ($node = $this->requestStack->getCurrentRequest()->get('node')) {
      $current_bid = empty($node->book['bid']) ? 0 : $node->book['bid'];
    }

    if ($this->configuration['block_mode'] == 'all pages') {
      return parent::build();
    }
    elseif ($current_bid) {
      // Only display this block when the user is browsing a book and do
      // not show unpublished books.
      $nid = \Drupal::entityQuery('node')
        ->condition('nid', $node->book['bid'], '=')
        ->condition('status', NODE_PUBLISHED)
        ->execute();
      // Only show the block if the user has view access for the top-level node.
      if ($nid) {
        $tree = $this->bookManager->bookTreeAllData($current_bid);
        $build = $this->bookManager->bookTreeOutput($tree);
        // Add active trail to theme.
        $active_trail = $this->bookManager->getActiveTrailIds($node->book['bid'], $node->book);
        $build['#active_trail'] = $active_trail;
        $build['#theme'] = 'jcc_book_navigation_block';
        return $build;
      }
    }
    return [];
  }

}
