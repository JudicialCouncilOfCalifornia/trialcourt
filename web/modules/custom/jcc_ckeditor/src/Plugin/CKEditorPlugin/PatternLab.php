<?php

namespace Drupal\jcc_ckeditor\Plugin\CKEditorPlugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\ckeditor\CKEditorPluginCssInterface;

/**
 * Defines the "patternlab" plugin.
 *
 * @CKEditorPlugin(
 *   id = "patternlab",
 *   label = @Translation("Patternlab"),
 *   module = "ckeditor"
 * )
 */
class PatternLab extends PluginBase implements CKEditorPluginInterface, CKEditorPluginContextualInterface, CKEditorPluginCssInterface {

  protected $patternlabData;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->patternlabData = file_get_contents(\Drupal::root() . '/libraries/courtyard-artifact/1.x/public/styleguide/data/patternlab-data.json');
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'jcc_ckeditor') . '/js/plugins/patternlab/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $pattern_structure = json_decode($this->patternlabData);
    $menu_groups = $menu_items = [];

    $ignored_groups = [
      'protons',
      'atoms',
      'tests',
    ];
    $ignored_items = [
      'pages-all-forms',
      'pages-form-lookup',
    ];
    $menu_groups[] = 'PatternLab';
    $menu_items['PatternLab'] = [
      'label' => 'PatternLab',
      'group' => 'PatternLab',
      'icon' => '/' . drupal_get_path('module', 'jcc_ckeditor') . '/js/plugins/patternlab/patternlab.png',
    ];

    $items = [];
    foreach ($pattern_structure->navItems->patternTypes as $pattern) {
      if (in_array($pattern->patternTypeLC, $ignored_groups)) {
        continue;
      }
      $menu_groups[] = $items[] = $pattern->patternType;
      $menu_items[$pattern->patternType] = [
        'label' => $pattern->patternTypeUC,
        'group' => 'PatternLab',
      ];

      $pattern_items = [];
      foreach ($pattern->patternTypeItems as $pattern_type_items) {
        $menu_item_name = $pattern->patternType . '_' . $pattern_type_items->patternSubtype;

        $menu_groups[] = $pattern_items[] = $menu_item_name;
        $menu_items[$menu_item_name] = [
          'label' => $pattern_type_items->patternSubtypeUC,
          'group' => $pattern->patternType,
        ];

        $pattern_sub_items = [];
        foreach ($pattern_type_items->patternSubtypeItems as $pattern_subtype_items) {
          if ($pattern_subtype_items->patternName == 'View All' ||
            in_array($pattern_subtype_items->patternPartial, $ignored_items)
          ) {
            continue;
          }

          $menu_sub_item_name = $menu_item_name . '_' . $pattern_subtype_items->patternPartial;

          $pattern_sub_items[] = $menu_sub_item_name;
          $pattern_path = str_replace('.html', '.markup-only.html', $pattern_subtype_items->patternPath);
          $menu_items[$menu_sub_item_name] = [
            'label' => $pattern_subtype_items->patternName,
            'group' => $menu_item_name,
            'path' => base_path() . 'libraries/courtyard-artifact/1.x/public/patterns/' . $pattern_path,
          ];
        }
        $menu_items[$menu_item_name]['items'] = $pattern_sub_items;
      }
      $menu_items[$pattern->patternType]['items'] = $pattern_items;
    }
    $menu_items['PatternLab']['items'] = $items;

    return [
      'patternlab' => [
        'menuGroups' => $menu_groups,
        'menuItems' => $menu_items,
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCssFiles(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    // return !empty($this->patternlabData);
    return FALSE;
  }

}
