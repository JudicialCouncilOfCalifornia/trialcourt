/**
 * @file
 * Form adaptations js file.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.jccForms = {
    attach: function (context, settings) {
      var $form = $('.usa-form', context);

      // Early return if there is no form.
      if (!$form.length) {
        return;
      }

      // Remove the captcha if the user is logged in.
      if (settings.user.authenticated) {
        $form.find('.captcha-admin-links').hide();
      }

    }
  };
})(jQuery, Drupal);
