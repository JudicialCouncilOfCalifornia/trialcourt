<?php

/**
 * @file
 * Theme and preprocess functions for menus.
 */

/**
 * Implements hook_preprocess_block().
 */
function jcc_newsroom_preprocess_block(array &$variables) {
  if ($variables['plugin_id'] !== 'jcc_book_navigation_block' || empty($variables['content']['#active_trail'])) {
    return;
  }

  $items = $variables['content']['#items'];
  $root = reset($items);
  if (!$root) {
    return;
  }

  // Load the whole access-checked outline so closed branches can open without
  // navigating to their parent page. Passing the current link limits the tree
  // to that link's active trail.
  $book_manager = \Drupal::service('book.manager');
  $bid = $root['original_link']['bid'];
  $tree = $book_manager->bookTreeAllData($bid);
  $build = $book_manager->bookTreeOutput($tree);
  $items = $build['#items'] ?? [];
  jcc_newsroom_book_navigation_active_trail($items, $variables['content']['#active_trail']);
  $variables['content']['#items'] = $items;
  $variables['content']['#cache']['tags'][] = 'bid:' . $bid;
  $variables['content']['#collapsible'] = TRUE;
}

/**
 * Restores the current branch after loading the complete book outline.
 */
function jcc_newsroom_book_navigation_active_trail(array &$items, array $active_trail) {
  foreach ($items as &$item) {
    $item['in_active_trail'] = in_array($item['original_link']['nid'], $active_trail);
    jcc_newsroom_book_navigation_active_trail($item['below'], $active_trail);
  }
}
