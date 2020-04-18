<?php

/**
 * Implements template_preprocess_filter_caption().
 */
function jcc_base_preprocess_filter_caption(&$variables) {
  // Do this for img tags only.
  if (($variables['tag'] !== 'img')) {
    return;
  }

  // Add the figure-img class to the rendered image markup.
  libxml_use_internal_errors(true);
  $doc = new DOMDocument();
  $doc->loadHTML($variables['node']);
  if ($image = $doc->getElementsByTagName('img')->item(0)) {
    $image->setAttribute('class', 'figure-img img-fluid');
    $variables['node'] = [
      '#markup' => $image->ownerDocument->saveHTML($image),
    ];
  }
}
