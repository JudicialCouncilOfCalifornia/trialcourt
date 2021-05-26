<?php

/**
 * @file
 * Preprocessors for views.
 */

use Drupal\Core\Render\Markup;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_deprep_preprocess_views_view_field__body(&$variables) {
  $node = $variables['row']->_entity;
  if ($node->getType() == 'document') {
    $document_type = $node->get('field_document_type')->entity->getName();

    if ($document_type == 'Case Summaries') {
      $title = $node->getTitle();
      $year = $node->get('field_upload_date')->getString() ?
        '(' . date('Y', strtotime($node->get('field_upload_date')
          ->getString())) . ')'
        : '';
      $citation = $node->get('field_links')->title;

      $doc = new DOMDocument();
      $doc->formatOutput = TRUE;

      if (isset($variables['output']) && $variables['output']) {
        $doc->loadXML($variables['output']->__toString());

        $citation_title = "<i>{$title}</i> {$year} {$citation}";
        $citation_title = preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', $citation_title);

        $fragment = $doc->createDocumentFragment();
        $fragment->appendXML($citation_title);

        $doc->getElementsByTagName("a")->item(0)->nodeValue = '';
        $doc->getElementsByTagName("a")->item(0)->appendChild($fragment);
      }

      $variables['output'] = Markup::create($doc->saveHTML());
    }
  }
}
