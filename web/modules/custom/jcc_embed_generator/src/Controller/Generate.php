<?php

namespace Drupal\jcc_embed_generator\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Deletes all groups from user.
 */
class Generate extends ControllerBase {

  /**
   * Returns a render-able array.
   */
  public function content() {

    $build = [
      '#markup' => '
      <div class="jcc_generator_embed">
        <div class="generator_form">
            <div class="form-item" id="data-count"></div>
            <div class="form-item" id="data-hide-date"></div>
            <div class="form-item" id="data-hide-description"></div>
            <div class="form-item" id="block-width"></div>
        </div>
        <div class="generator_result_wrapper">
        </div>
      </div>
      ',
      '#attached' => [
        'library' => [
          'jcc_embed_generator/jcc_embed_generator_js',
        ],
      ],
    ];

    return $build;
  }

}
