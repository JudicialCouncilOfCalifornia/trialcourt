<?php

namespace Drupal\jcc_elevated_find_my_court\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Example module.
 */
class FindMyCourt extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function findMyCourt() {
    return [
      '#type' => 'inline_template',
      '#template' => '<div class="section__content box"><div class="container stack"><iframe src="{{ url }}" id="iframe-ftrc" class="iframe-ftrc" title="County courthouses found by zip code or city" style="height: 2390.37px;width:100%;"></iframe></div></div>',
      '#context' => [
        'url' => 'modules/custom/jcc_elevated_find_my_court/src/ftrc/index.html?query=94117',
      ],
    ];
  }

}
