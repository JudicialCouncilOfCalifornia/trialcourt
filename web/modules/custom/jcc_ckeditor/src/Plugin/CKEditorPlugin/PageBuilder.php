<?php

namespace Drupal\jcc_ckeditor\Plugin\CKEditorPlugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\ckeditor\CKEditorPluginCssInterface;

/**
 * Defines the "pagebuilder" plugin.
 *
 * @CKEditorPlugin(
 *   id = "pagebuilder",
 *   label = @Translation("PageBuilder"),
 *   module = "ckeditor"
 * )
 */
class PageBuilder extends PluginBase implements CKEditorPluginInterface, CKEditorPluginContextualInterface, CKEditorPluginCssInterface {

  protected $patternlabData;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    $this->patternlabData = file_get_contents(\Drupal::root() . '/libraries/courtyard-artifact/public/styleguide/data/patternlab-data.json');
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
    return drupal_get_path('module', 'jcc_ckeditor') . '/js/plugins/pagebuilder/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $pattern_structure = json_decode($this->patternlabData);
    $menu_groups = $menu_items = [];

    $path = base_path() . drupal_get_path('module', 'jcc_ckeditor') . '/js/plugins/pagebuilder/components';
    $pagebuilder = [
      "grid" => [
        "label" => 'Grid',
        "items" => [
          "12" => [
            "label" => "Single (12)",
            "path" => $path . "/grid/12.html",
          ],
          "6-6" => [
            "label" => "Double (6-6)",
            "path" => $path . "/grid/6-6.html",
          ],
          "8-4" => [
            "label" => "Double (8-4)",
            "path" => $path . "/grid/8-4.html",
          ],
          "4-8" => [
            "label" => "Double (4-8)",
            "path" => $path . "/grid/4-8.html",
          ],
          "4-4-4" => [
            "label" => "Triple (4-4-4)",
            "path" => $path . "/grid/4-4-4.html",
          ],
          "3-3-3-3" => [
            "label" => "Quadruple (3-3-3-3)",
            "path" => $path . "/grid/3-3-3-3.html",
          ],
        ],
      ],
      "header" => [
        "label" => "Header",
        "items" => [
          "center" => [
            "label" => "Center",
            "path" => $path . "/header/center.html",
          ],
          "left" => [
            "label" => "Left",
            "path" => $path . "/header/left.html",
          ],
          "top" => [
            "label" => "Top",
            "path" => $path . "/header/top.html",
          ],
        ],
      ],
      'card' => [
        "label" => 'Card',
        "items" => [
          'default' => [
            "label" => 'Default',
            "items" => [
              "light" => [
                "label" => "Light",
                "path" => $path . "/card/default/light.html",
              ],
              "medium" => [
                "label" => "Medium",
                "path" => $path . "/card/default/medium.html",
              ],
              "dark" => [
                "label" => "Dark",
                "path" => $path . "/card/default/dark.html",
              ],
            ]
          ],
          "image" => [
            "label" => "Image",
            "items" => [
              "vertical" => [
                "label" => "Vertical",
                "path" => $path . "/card/image/vertical.html",
              ],
              "horizontal" => [
                "label" => "Horizontal",
                "path" => $path . "/card/image/horizontal.html",
              ],
            ],
          ],
          "icon" => [
            "label" => "Icon",
            "items" => [
              "icon-title" => [
                "label" => "Mini",
                "path" => $path . "/card/icon/icon-title.html",
              ],
              "icon-large" => [
                "label" => "Large",
                "path" => $path . "/card/icon/icon-large.html",
              ],
            ],
          ],
        ],
      ],
      "links" => [
        "label" => "Links",
        "items" => [
          "tile" => [
            "label" => "Tile",
            "path" => $path . "/links/tile.html",
          ],
          "list-button" => [
            "label" => "Button List",
            "path" => $path . "/links/list-button.html",
          ],
          "list-box" => [
            "label" => "Box List",
            "path" => $path . "/links/list-box.html",
          ],
        ],
      ],
      "text" => [
        "label" => "Text",
        "items" => [
          "accordion" => [
            "label" => "Accordion",
            "path" => $path . "/text/accordion.html",
          ],
          "blockquote" => [
            "label" => "Blockquote",
            "items" => [
              "large" => [
                "label" => "Large",
                "path" => $path . "/text/blockquote/blockquote.html",
              ],
              "compact" => [
                "label" => "Compact",
                "path" => $path . "/text/blockquote/blockquote-compact.html",
              ],
            ],
          ],
          "read-more" => [
            "label" => "Read More",
            "path" => $path . "/text/read-more.html",
          ],
          "table" => [
            "label" => "Table",
            "path" => $path . "/text/table.html",
          ],
        ],
      ],
    ];

    $menu_groups[] = 'PageBuilder';
    $menu_items['PageBuilder'] = [
      "label" => 'PageBuilder',
      'group' => 'PageBuilder',
      'icon' => '/' . drupal_get_path('module', 'jcc_ckeditor') . '/js/plugins/pagebuilder/pagebuilder.png',
    ];

    $items = [];
    foreach ($pagebuilder as $group_id => $pattern) {
      $menu_groups[] = $items[] = $group_id;
      $menu_items[$group_id] = [
        "label" => $pattern["label"],
        'group' => 'PageBuilder',
      ];

      $pattern_items = [];
      foreach ($pattern["items"] as $pattern_item_id => $pattern_item) {
        $menu_item_name = $group_id . '_' . $pattern_item_id;

        $menu_groups[] = $pattern_items[] = $menu_item_name;
        $menu_items[$menu_item_name] = [
          "label" => $pattern_item["label"],
          'group' => $group_id,
          "path" => isset($pattern_item["path"]) ? $pattern_item["path"] : '',
        ];

        $pattern_sub_items = [];
        if (isset($pattern_item["items"]) && count($pattern_item["items"])) {
          foreach ($pattern_item["items"] as $pattern_subitem_id => $pattern_subitem) {

            $menu_sub_item_name = $menu_item_name . '_' . $pattern_subitem_id;
            $pattern_sub_items[] = $menu_sub_item_name;
            $menu_items[$menu_sub_item_name] = [
              "label" => $pattern_subitem["label"],
              'group' => $menu_item_name,
              "path" => isset($pattern_subitem["path"]) ? $pattern_subitem["path"] : '',
            ];

          }
        }
        $menu_items[$menu_item_name]["items"] = $pattern_sub_items;
      }
      $menu_items[$group_id]["items"] = $pattern_items;
    }
    $menu_items['PageBuilder']["items"] = $items;

    return [
      'pagebuilder' => [
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
    return TRUE;
  }

}
