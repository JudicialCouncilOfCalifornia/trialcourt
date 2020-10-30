<?php

/**
 * @file
 * Preprocessors for views.
 */

use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_deprep_preprocess_views_view_field__body(&$variables) {
  $node = $variables['row']->_entity;
  $document_type = $node->get('field_document_type')->entity->getName();

  if ($document_type == 'Case Summaries') {
    $title = $node->getTitle();
    $year = $node->get('field_upload_date')->getString() ?
      '(' . date('Y', strtotime($node->get('field_upload_date')->getString())) . ')'
      : '';
    $citation = $node->get('field_links')->title;

    $doc = new DOMDocument();
    $doc->formatOutput = TRUE;
    $doc->loadXML($variables['output']->__toString());

    $fragment = $doc->createDocumentFragment();
    $fragment->appendXML("<i>{$title}</i> {$year} {$citation}");

    $doc->getElementsByTagName("a")->item(0)->nodeValue  = '';
    $doc->getElementsByTagName("a")->item(0)->appendChild($fragment);

    $variables['output'] = Drupal\Core\Render\Markup::create($doc->saveHTML());
  }
}
