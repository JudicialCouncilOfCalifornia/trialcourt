<?php

/**
 * @file
 * Utility code related to paragraphs.
 */

/**
 * Implements hook_preprocess_paragraph().
 */
function jcc_base_preprocess_paragraph__column_layout(&$variables) {
  $content = $variables['content'];
  $id = NULL;
  $classes = NULL;
  $blocks = NULL;
  if (isset($content['field_id'][0]['#context']['value'])) {
    $id = $content['field_id'][0]['#context']['value'];
  }
  if (isset($content['field_css_classes'][0]['#context']['value'])) {
    $classes = $content['field_css_classes'][0]['#context']['value'];
  }
  if (isset($content['regions'])) {
    $blocks = render($content['regions']);
  }

  $variables['paragraph_layout'] = [
    'id' => $id,
    'classes' => $classes,
    'blocks' => $blocks,
  ];

}
