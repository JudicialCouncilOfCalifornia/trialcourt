/**
 * @file
 * JCC Messaging center behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Behavior description.
   */
  Drupal.behaviors.jccMessagingCenter = {
    attach: function (context, settings) {
      // enabling hidden field for subscriptions
      if($('.user-register-form', context).length){
        var urlParams = new URLSearchParams(window.location.search);
        var stringargs = urlParams.getAll('preselected');
        if(stringargs.length){
          $('.user-register-form .field--name-field-group .form-checkboxes input', context).prop('checked', false);
          var args = stringargs[0].split(',');
          for (var i = 0; i < args.length; i++) {
            $('.user-register-form .field--name-field-group .form-checkboxes input[value='+args[i]+']', context).prop('checked', true);
          }
        }
      }
    }
  }
} (jQuery, Drupal));
