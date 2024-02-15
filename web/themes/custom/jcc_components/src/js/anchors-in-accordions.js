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
  
  // Attach a click event listener to all anchor tags within 'section' elements.
  $(document).on('click', 'section a', function(event) {  
    // Retrieve the href attribute of the clicked link, which is the ID of the target element.
    var targetId = $(this).attr('href'); 
    // Determine if the target element is within an accordion by checking if any of its parent divs has a class that includes 'usa-accordion'.
    var isInAccordion = $(targetId).closest("div").attr("class").includes("usa-accordion");   
    // If the target is within an accordion, find the ID of the closest accordion container.
    var accordionContainerId = isInAccordion ? $(targetId).closest("div").attr("id") : null;     
    // If the target element is within an accordion, expand the accordion section.
    if (isInAccordion) {
        // Find the button that controls the accordion section and set 'aria-expanded' to true, indicating the section is expanded.
        $(targetId).closest("div").prev().children("button").attr('aria-expanded', true);
        // Remove the 'hidden' property from the accordion content to show it.
        $(targetId).closest("div").prop('hidden', false);
    }
  });
})(jQuery, Drupal);

