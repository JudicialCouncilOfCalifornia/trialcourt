<?php

/**
 * @file
 * Theme and preprocess functions for pages.
 */

use Drupal\jcc_blocks\Plugin\Block\JccHeroBlock;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_page__404(&$variables) {
  $featuredLinks = JccHeroBlock::getFeaturedLinks();
  $links = [];
  if (isset($featuredLinks['icons'])) {
    $icons = $featuredLinks['icons'];
    foreach ($icons as $icon) {
      $linkTitle = $icon['title'];
      $linkUrl = $icon['url'];

      $link = [
        'url_title' => $linkTitle,
        'url' => $linkUrl,
      ];

      array_push($links, $link);
    }
  }

  $variables['my_link_list'] = [
    'file_list' => FALSE,
    'items' => $links,
  ];
}
