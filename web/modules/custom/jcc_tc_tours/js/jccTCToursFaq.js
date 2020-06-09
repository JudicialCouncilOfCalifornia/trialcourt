(function ($, Drupal) {
  Drupal.behaviors.jccTCToursFaq = {
    attach: function (context, settings) {

      // Open the MetaData tab for the next step
      $(document).on("click", ".tip-faq-answer .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(1) a').click();
      });

    }
  };
})(jQuery, Drupal);
