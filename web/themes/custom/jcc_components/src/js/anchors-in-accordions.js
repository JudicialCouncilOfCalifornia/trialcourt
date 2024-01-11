/**
* Nav tabs enhancement from seven theme.
**/

(function ($, Drupal) {
  Drupal.behaviors.anchorsInAccordions = {
    attach: function attach(context, settings) {
      $('main').find("a[href*='#']").click(function(e){
        var elemId = $(this).attr('href');
        elemId = elemId.replace('#','');
        if ($('#' + elemId).parents('.usa-accordion__content').attr('hidden')) {
          $('#' + elemId).parents('.usa-accordion__content').removeAttr('hidden');
        }
      });
    }
  };
  })(jQuery, Drupal);
