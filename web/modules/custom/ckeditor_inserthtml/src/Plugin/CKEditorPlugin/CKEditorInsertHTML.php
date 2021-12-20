<?php

namespace Drupal\ckeditor_inserthtml\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "inserthtml4x" plugin.
 *
 * @CKEditorPlugin(
 *   id = "inserthtml4x",
 *   label = @Translation("Insert HTML")
 * )
 */
class CKEditorInsertHTML extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/ckeditor/plugins/inserthtml4x/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'inserthtml4x' => [
        'label' => t('Insert HTML'),
        'image' => 'libraries/ckeditor/plugins/inserthtml4x/inserthtml.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
