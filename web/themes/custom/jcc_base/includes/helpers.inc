<?php

/**
 * @file
 * Theme and preprocess functions for blocks.
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Url;

/**
 * Implements helper function.
 */
function _jcc_list_faceted_terms($node, $vocabularies) {
  $tags_markup = '';
  $unique_tags = [];
  foreach ($vocabularies as $tag) {
    $display_options = EntityViewDisplay::collectRenderDisplay($node, 'teaser')
      ->getComponent($tag);
    $tag_render = [];
    $tag_render = $node->$tag->view($display_options);

    // Remove duplicate term if the same name exists in another vocab.
    if (isset($tag_render['#items']) && $tag_render['#items']) {
      foreach ($tag_render['#items'] as $key => $item) {
        if ($item->get('entity') != NULL) {
          continue;
        }
        $name = strtolower($item->get('entity')
          ->getTarget()
          ->getValue()
          ->getName());

        if (!in_array($name, $unique_tags)) {
          $unique_tags[] = $name;
        }
        else {
          unset($tag_render[$key]);
        }
      }
    }

    $tags_markup .= render($tag_render);
  }

  // NEW-35.
  $tags_markup = _rewrite_tag_links($tags_markup);

  return $tags_markup;
}

/**
 * Helper function to remove protocol from image paths.
 *
 * @param string $image
 *   The Drupal image url.
 *
 * @return string
 *   Converted url with no http or https.
 */
function _jcc_components_remove_image_protocol($image) {
  return preg_replace('(^https?:||^http?:)', '', $image);
}

/**
 * Builds a valid internal URL from a base path and a page slug.
 *
 * @param string $base
 *   The base path (e.g., 'news').
 * @param string $page
 *   The page part (e.g., 'abc').
 *
 * @return string
 *   A properly formed internal URL string, like '/news/abc'.
 */
function build_internal_url($base, $page) {
  $base = trim($base, '/');
  $page = trim($page, '/');
  $path = $base . '/' . $page;

  return Url::fromUri('base:/' . $path)->toString();
}

/**
 * Builds a map of titles to internal Drupal URLs.
 *
 * @return array
 *   An associative array mapping title keys to internal URLs.
 */

/**
 * Builds a map of titles to internal Drupal URLs.
 *
 * @return array
 *   An associative array mapping title keys to internal URLs.
 */
function build_custom_url_map() {
  $raw_path_map = [
    "Supreme Court" => "supreme-court",
    "Courts of Appeal" => "courts-of-appeal",
    "Superior Courts" => "supreme-court",
    "Judicial Council" => "judicial-council",
    "Chief Justice" => "chief-justice",
  ];

  $custom_url_map = [];
  foreach ($raw_path_map as $title => $path) {
    $custom_url_map[$title] = build_internal_url('news', $path);
  }

  return $custom_url_map;
}

/**
 * Rewrite the urls with known mapping.
 *
 * @param $markup
 *   an object whose links need modification
 *
 * @return
 *   A properly formed object resulting from the url rewriting
 */
function _rewrite_tag_links($markup) {

  $custom_url_map = build_custom_url_map();

  $doc = new \DOMDocument();
  // Suppress warnings for malformed HTML.
  libxml_use_internal_errors(TRUE);
  $doc->loadHTML('<?xml encoding="utf-8" ?>' . $markup);
  libxml_clear_errors();

  $links = $doc->getElementsByTagName('a');
  foreach ($links as $link) {
    $title = trim($link->nodeValue);
    if (isset($custom_url_map[$title])) {
      $link->setAttribute('href', $custom_url_map[$title]);
    }
  }

  // Extract the body content (excluding HTML/head tags)
  $body = $doc->getElementsByTagName('body')->item(0);
  $new_markup = '';
  foreach ($body->childNodes as $child) {
    $new_markup .= $doc->saveHTML($child);
  }
  return $new_markup;
}
