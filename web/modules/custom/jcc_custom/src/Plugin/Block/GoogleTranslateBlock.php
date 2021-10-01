<?php

namespace Drupal\jcc_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Google Translate Block.
 *
 * @Block(
 *   id = "google_translate_block",
 *   admin_label = @Translation("Google Translate block"),
 * )
 */
class GoogleTranslateBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => '<div id="google_translate_element"></div>',
      '#attached' => [
        'library' => [
          'jcc_custom/googletranslate',
        ],
      ],
    ];
  }

}
