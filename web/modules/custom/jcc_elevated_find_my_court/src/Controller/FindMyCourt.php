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
      '#markup' => $this->t('<div>
        <script type="text/javascript">
          document.addEventListener("DOMContentLoaded", function() {
            let iframe = document.getElementsByTagName("iframe")[0];
            if (iframe) {
              let iframeID = \'#\' + document.getElementsByTagName("iframe")[0].id;
              iFrameResize({lowestElement: true}, iframeID);
            }
          });
        </script>
        <div class="hangover hangover--solid-primary-dark-x" style="--hangover-offset: -22ch;">
          <div class="hangover__top">
            <div class="box">
              <div class="with-sidebar detect-wrap detect-wrap--observed container">
                <div class="hangover__top-content">
                  <div class="stack">
                    <div class="stack">
                      <h1 class="hangover__title stack__sm-space">Find my court</h1>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="section box"></div>
          <div class="container stack">
            <div class="box">
              <div class="inline-form inline-form--default" style="max-width:40rem;margin:auto;">
                <form class="views-exposed-form bef-exposed-form" onsubmit="this.action = \'/find-my-court/\' + this.urlarg.value;">
                  <div class="form-item">
                    <input
                      class="form-text"
                      placeholder="Enter a city or a zip code"
                      type="text"
                      value="@zip"
                      name="urlarg"
                      id="urlarg"
                    />
                  </div>
                  <div class="form-actions form-wrapper" id="edit-actions">
                    <button class=\'button form-submit\' type=\'submit\'>
                      <span class=\'inline-form__submit-line\'></span>
                      <span class=\'inline-form__submit-circle\'></span>
                      <span class=\'sr-only\'>Search</span>
                    </button>
                  </div>
                </form>
                <div class="description">
                  <div id="edit-text-field-label--description" class="webform-element-description">
                    Please enter a 5-digit California zip code (e.g. 92110) or a city name (e.g. Oakland)
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="container stack">
            <div class="box">
             <iframe src="/modules/custom/jcc_elevated_find_my_court/src/ftrc/index.html?query=@zip" id="iframe-ftrc" class="iframe-ftrc" title="County courthouses found by zip code or city" style="height: 2390.37px;width:100%;" frameBorder="0"></iframe>
            </div>
          </div>
         </div>
        </div>', ['@zip' => $zip]),
    ];

    $output['#attached']['library'][] = 'jcc_elevated_find_my_court/storybook-inlineform';
    $output['#attached']['library'][] = 'jcc_elevated_find_my_court/storybook-herohangover';
    $output['#attached']['library'][] = 'jcc_elevated_find_my_court/iframeresizer';
    return $output;
  }

}
