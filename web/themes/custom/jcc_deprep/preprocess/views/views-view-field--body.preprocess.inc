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

      $doc = new DOMDocument();
      $doc->formatOutput = TRUE;

      if (isset($variables['output']) && $variables['output']) {
        $doc->loadXML($variables['output']->__toString());

        $fragment = $doc->createDocumentFragment();
        $fragment->appendXML($title);

        if ($doc->getElementsByTagName("a")->item(0)) {
          $doc->getElementsByTagName("a")->item(0)->nodeValue = '';
          $doc->getElementsByTagName("a")->item(0)->appendChild($fragment);
        }
      }

      $variables['output'] = Markup::create($doc->saveHTML());
    }
  }
}
