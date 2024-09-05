<?php

/**
 * @file
 * Contains jcc_tc2_roc_feature ckeditor module functionality.
 */

use Drupal\editor\Entity\Editor;
use Drupal\node\NodeInterface;

/**
 * Implements hook_editor_js_settings_alter().
 */
function jcc_tc2_roc_feature_editor_js_settings_alter(array &$settings): void {

  // Change the style set on rule_subsection nodes with RuleFormatting items.
  foreach (array_keys($settings['editor']['formats']) as $text_format_id) {
    if ($settings['editor']['formats'][$text_format_id]['editor'] === 'ckeditor') {

      $route_match = \Drupal::routeMatch();
      $route_name = $route_match->getRouteName();
      $path = Drupal::request()->getPathInfo();
      $alter_ckeditor_for_rules = FALSE;

      // Check if a node edit route for rules.
      if ($route_name == 'entity.node.edit_form') {
        $current_node = Drupal::request()->attributes->get('node');
        if ($current_node instanceof NodeInterface) {
          if ($current_node->bundle() == 'roc_rule') {
            $alter_ckeditor_for_rules = TRUE;
          }
        }
      }

      // Check if a node add route for rules.
      if ($route_name == 'node.add' && $path == '/node/add/roc_rule') {
        $alter_ckeditor_for_rules = TRUE;
      }

      if ($alter_ckeditor_for_rules) {
        // Control our available styles for Rules editing.
        $styles = [];
        foreach (jcc_tc2_roc_feature_rule_styles_info() as $style) {
          $styles[] = $style;
        }
        $settings['editor']['formats'][$text_format_id]['editorSettings']['stylesSet'] = $styles;

        // Control available tools for Rules editing.
        $tools_to_remove = [
          'DrupalMediaLibrary',
          'tooltip',
          'paragraphs_inline_entity_form',
          'BidiLtr',
          'BidiRtl',
          'Subscript',
          'Superscript',
          'Format',
        ];
        foreach ($settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'] as $g_delta => $toolbar_group) {
          if (is_array($toolbar_group)) {
            foreach ($toolbar_group['items'] as $t_delta => $tool_name) {
              if (in_array($tool_name, $tools_to_remove)) {
                unset($settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items'][$t_delta]);
              }
            }
            $cleaned_array = array_values($settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items']);
            $settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items'] = $cleaned_array;

            if ($toolbar_group['name'] == 'Tools') {
              if (!in_array('A11ychecker', $settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items'])) {
                $settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items'][] = 'A11ychecker';
              }
              if (!in_array('Maximize', $settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items'])) {
                $settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items'][] = 'Maximize';
              }
            }
            if ($toolbar_group['name'] == 'Lists') {
              if (!in_array('Outdent', $settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items'])) {
                $settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items'][] = 'Outdent';
              }
              if (!in_array('Indent', $settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items'])) {
                $settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items'][] = 'Indent';
              }
            }
            if ($toolbar_group['name'] == 'Formatting') {
              if (!in_array('JustifyLeft', $settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items'])) {
                $settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items'][] = 'JustifyLeft';
              }
              if (!in_array('JustifyCenter', $settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items'])) {
                $settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items'][] = 'JustifyCenter';
              }
              if (!in_array('JustifyRight', $settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items'])) {
                $settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items'][] = 'JustifyRight';
              }
              if (!in_array('JustifyBlock', $settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items'])) {
                $settings['editor']['formats'][$text_format_id]['editorSettings']['toolbar'][$g_delta]['items'][] = 'JustifyBlock';
              }
            }
          }
        }
      }
    }
  }
}

/**
 * Implements hook_ckeditor_css_alter().
 */
function jcc_tc2_roc_feature_ckeditor_css_alter(array &$css, Editor $editor): void {
  if (jcc_custom_is_elevated_site() && in_array($editor->getFilterFormat()->id(), ['body'])) {
    if (!$editor->hasAssociatedFilterFormat()) {
      return;
    }

    $route_match = \Drupal::routeMatch();
    $route_name = $route_match->getRouteName();
    $path = Drupal::request()->getPathInfo();
    $alter_ckeditor_for_rules = FALSE;

    // Check if an edit route.
    if (in_array($route_name, ['entity.node.edit_form', 'node.add'])) {
      $current_node = \Drupal::request()->attributes->get('node');
      if ($current_node instanceof NodeInterface) {
        if ($current_node->bundle() == 'roc_rule') {
          $alter_ckeditor_for_rules = TRUE;
        }
      }
    }

    // Check if a node add route for rules.
    if ($route_name == 'node.add' && $path == '/node/add/roc_rule') {
      $alter_ckeditor_for_rules = TRUE;
    }

    if ($alter_ckeditor_for_rules) {
      $css[] = "/themes/contrib/jcc_storybook/lib/stories/Atoms/RulesFormatting/RulesFormatting.css";
    }
  }
}

/**
 * Sets up new list of styles for rule subsections.
 */
function jcc_tc2_roc_feature_rule_styles_info(): array {
  $styles = [];

  $styles[] = [
    'name' => 'Normal',
    'element' => 'p',
    'attributes' => [
      'class' => 'normal',
    ],
  ];

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
    'name' => 'Item List',
    'element' => 'p',
    'attributes' => [
      'class' => 'itemlist',
    ],
  ];

  $styles[] = [
    'name' => 'Item No List',
    'element' => 'p',
    'attributes' => [
      'class' => 'itemnolist',
    ],
  ];

  $styles[] = [
    'name' => 'SubItem List',
    'element' => 'p',
    'attributes' => [
      'class' => 'subitemlist',
    ],
  ];

  $styles[] = [
    'name' => 'SubItem No List',
    'element' => 'p',
    'attributes' => [
      'class' => 'subitemnolist',
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

  $styles[] = [
    'name' => 'AdvComm Text',
    'element' => 'p',
    'attributes' => [
      'class' => 'advcommtext',
    ],
  ];

  $styles[] = [
    'name' => 'Add grid/borders',
    'element' => 'table',
    'attributes' => [
      'class' => 'add-borders',
    ],
  ];

  return $styles;
}
