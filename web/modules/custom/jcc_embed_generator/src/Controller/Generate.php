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
    $term_select_view = views_embed_view('taxonomy_term_list', 'default');
    if ($term_select_view != NULL) {
      $term_select = \Drupal::service('renderer')->render($term_select_view);
    }
    else {
      $term_select = "";
    }

    $build = [
      '#markup' => '
      <div class="jcc_generator_embed">
        <h1 class="jcc-title">Embed California Courts news on your website</h1>
        <div class="tag-list">' . $term_select . '</div>
        <div class="generator_form">
            <div class="left-col">
                <div class="form-item" id="term-selector"></div>
                <div class="form-item" id="selected-tags"></div>
            </div>
            <div class="right-col">
                <div class="form-item" id="data-count"></div>
                <div class="jcc-embed-style">
                    <label>Set the style of the widget</label>
                    <div class="form-item jcc-checkbox" id="data-hide-thumbnail"></div>
                    <div class="form-item jcc-checkbox" id="data-hide-date"></div>
                    <div class="form-item jcc-checkbox" id="data-hide-description"></div>
                    <div class="form-item" id="block-width"></div>
                </div>
            </div>
        </div>
        <div class="generated-dashboard">
          <div class="generator_result_wrapper">
          </div>
          <div class="generator_result_preview">
          </div>
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
