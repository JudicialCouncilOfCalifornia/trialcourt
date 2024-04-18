<?php

/**
 * @file
 * Contains jcc_roc ckeditor module functionality.
 */

use Drupal\node\NodeInterface;

/**
 * Implements hook_editor_js_settings_alter().
 */
function jcc_roc_editor_js_settings_alter(array &$settings): void {

  // Change the style set on rule_subsection nodes with RuleFormatting items.
  foreach (array_keys($settings['editor']['formats']) as $text_format_id) {
    if ($settings['editor']['formats'][$text_format_id]['editor'] === 'ckeditor') {
      $styles = $settings['editor']['formats'][$text_format_id]['editorSettings']['stylesSet'];

      $route_match = \Drupal::routeMatch();
      $route_name = $route_match->getRouteName();

      // Check if an edit route.
      if (in_array($route_name, ['entity.node.edit_form', 'node.add'])) {
        $current_node = \Drupal::request()->attributes->get('node');
        if ($current_node instanceof NodeInterface) {
          if ($current_node->bundle() == 'rule_subsection') {
            $styles = [];
            foreach (jcc_roc_subsection_styles_info() as $style) {
              $styles[] = $style;
            }
          }
        }
      }

      $settings['editor']['formats'][$text_format_id]['editorSettings']['stylesSet'] = $styles;
    }
  }
}

/**
 * Sets up new list of styles for rule subsections.
 */
function jcc_roc_subsection_styles_info(): array {
  $styles = [];

  $styles[] = [
    'name' => 'Subdivision Heading',
    'element' => 'p',
    'attributes' => [
      'class' => 'subdivheading',
    ],
  ];

  $styles[] = [
    'name' => 'Subdivision Text',
    'element' => 'p',
    'attributes' => [
      'class' => 'subdivtext',
    ],
  ];

  $styles[] = [
    'name' => 'Paragraph List',
    'element' => 'p',
    'attributes' => [
      'class' => 'paragraphlist',
    ],
  ];

  $styles[] = [
    'name' => 'Unordered List',
    'element' => 'ul',
    'attributes' => [
      'class' => 'paragraphlist',
    ],
  ];

  $styles[] = [
    'name' => 'Paragraph No List',
    'element' => 'p',
    'attributes' => [
      'class' => 'paragraphnolist',
    ],
  ];

  $styles[] = [
    'name' => 'Subparagraph List',
    'element' => 'p',
    'attributes' => [
      'class' => 'subparagraphlist',
    ],
  ];

  $styles[] = [
    'name' => 'Subparagraph No List',
    'element' => 'p',
    'attributes' => [
      'class' => 'subparagraphnolist',
    ],
  ];

  $styles[] = [
    'name' => 'Numbered List',
    'element' => 'ol',
    'attributes' => [
      'class' => 'numberedlist',
    ],
  ];

  $styles[] = [
    'name' => 'Numbered List',
    'element' => 'p',
    'attributes' => [
      'class' => 'numberedlist',
    ],
  ];

  $styles[] = [
    'name' => 'Subd History',
    'element' => 'p',
    'attributes' => [
      'class' => 'subdhist',
    ],
  ];

  $styles[] = [
    'name' => 'Rule History',
    'element' => 'p',
    'attributes' => [
      'class' => 'rulehist',
    ],
  ];

  $styles[] = [
    'name' => 'AdvComm Heading',
    'element' => 'p',
    'attributes' => [
      'class' => 'advcommheader',
    ],
  ];

  $styles[] = [
    'name' => 'AdvComm Text',
    'element' => 'ol',
    'attributes' => [
      'class' => 'advcommtext',
    ],
  ];

  return $styles;

}
