/**
 * @file
 * Feedback Survey js file.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.jccFeedbackSurvey = {
    attach: function (context, settings) {
      const $surveyContainer = $('#feedback-survey');
      const $triggerBtn = $surveyContainer.find('[data-offcanvas="trigger"]');
      const $surveyYesBtn = $surveyContainer.find('[data-surveyvalue="yes"]');
      const $surveyNoBtn = $surveyContainer.find('[data-surveyvalue="no"]');
      const $actualYesRadio = $surveyContainer.find('[name="was_helpful"][value="Yes"]');
      const $actualNoRadio = $surveyContainer.find('[name="was_helpful"][value="No"]');

      // Avoid survey scroll to top behavior
      $triggerBtn.attr('href', '');
      $triggerBtn.on('click', function (e) {
        e.preventDefault();
      });

      // Sync offcanvas component action and actual webform radio buttons
      $surveyYesBtn.on('click', function(e) {
        e.preventDefault();
        chooseOption(this, $actualYesRadio);
      });

      $surveyNoBtn.on('click', function(e) {
        e.preventDefault();
        chooseOption(this, $actualNoRadio);
      });

      function chooseOption(elem, actualRadio) {
        $(actualRadio).click();
        $surveyContainer.find('.jcc-button--active').removeClass('jcc-button--active');
        $(elem).addClass('jcc-button--active');
      }
    }
  };
})(jQuery, Drupal);
