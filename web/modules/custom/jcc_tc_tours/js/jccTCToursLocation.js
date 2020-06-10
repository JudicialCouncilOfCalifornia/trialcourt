(function ($, Drupal) {
  Drupal.behaviors.jccTCToursLocation = {
    attach: function (context, settings) {

      $(document).on("click", ".tip-location-mailing-address .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(1) a').click();
      });

      $(document).on("click", ".tip-location-body .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(2) a').click();
      });

      $(document).on("click", ".tip-location-phone .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(3) a').click();
      });

      $(document).on("click", ".tip-location-phone-hours .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(4) a').click();
      });

      $(document).on("click", ".tip-location-metadata .joyride-next-tip", function() {
        $('#edit-location').attr('open', '');
      });
    }
  };
})(jQuery, Drupal);
