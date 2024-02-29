/**
* Nav tabs enhancement from seven theme.
**/

(function ($, Drupal) {
  Drupal.behaviors.anchorsInAccordions = {
    attach: function attach(context, settings) {
      $('main').find("a[href*='#']").click(function(e){
        var elemId = $(this).attr('href');
        //If accordion is closed
        if ($(elemId).parents('.usa-accordion__content').prop('hidden')){
          // Set the accordion body visible
          $(elemId).parents('.usa-accordion__content').prop('hidden', false)
          // Set the accordion header to show "-" sign for collapse
          $(elemId).parents('.usa-accordion__content').prev().children("button").attr('aria-expanded', true);
        }
      });
    }
  };
})(jQuery, Drupal);

