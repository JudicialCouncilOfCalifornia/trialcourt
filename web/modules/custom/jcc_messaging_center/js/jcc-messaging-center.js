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
      $('.jcc-messaging-center-section-form', context).each(function(index, element){
        $('.jcc-messaging-center-section-form .form-checkbox').prop('checked', false );
        var group_val = $(this).attr('data-group-values');
        var group_val_array = group_val.split("-");
        $('.jcc-messaging-center-section-form .fieldgroup .form-checkbox', context).each(function(index, element) {
          if(group_val_array.includes($(this).attr('value'))){
            $(this).prop('checked', true );
          }
        })
      })
    }
  }
} (jQuery, Drupal));
