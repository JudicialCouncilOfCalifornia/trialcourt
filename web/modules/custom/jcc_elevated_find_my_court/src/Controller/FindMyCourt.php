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
  public function findMyCourt($zip) {
    $output = [
      '#markup' => $this->t('<div class="section__content box">
        <div class="container stack">
            <form onsubmit="this.action = \'/find-my-court/\' + this.urlarg.value;">
                <div class="cluster center">
                    <div class="form-item form-item-text-field-label placeholder-container js-form-type-textfield">
			                  <input data-drupal-selector="edit-text-field-label" aria-describedby="edit-text-field-label--description" type="text" id="edit-text-field-label" name="urlarg" id="urlarg" value="" size="60" maxlength="255" class="form-text" placeholder="@zip" ">
                        <label for="edit-text-field-label">Zipcode</label>
                    </div>
                    <button class="button button--text button--normal icon--arrow" type="submit">
					              <span class="usa-sr-only">Search</span>
				            </button>
                </div>
            </form>
         </div>
         <div class="container stack">
            <iframe src="/modules/custom/jcc_elevated_find_my_court/src/ftrc/index.html?query=@zip" id="iframe-ftrc" class="iframe-ftrc" title="County courthouses found by zip code or city" style="height: 2390.37px;width:100%;" frameBorder="0"></iframe>
         </div>
        </div>', ['@zip' => $zip]),
    ];

    return $output;
  }

}
