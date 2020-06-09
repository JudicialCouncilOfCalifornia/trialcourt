(function ($, Drupal) {
  Drupal.behaviors.jccTCToursEvent = {
    attach: function (context, settings) {

      $(document).on("click", ".tip-event-extended-details .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(1) a').click();
      });

      $(document).on("click", ".tip-event-media .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(2) a').click();
      });

      $(document).on("click", ".tip-event-secondary-content .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(3) a').click();
      });

      $(document).on("click", ".tip-event-metadata .joyride-next-tip", function() {
        $('#edit-event').attr('open', '');
      });
    }
  };
})(jQuery, Drupal);
