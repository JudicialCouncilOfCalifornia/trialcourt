(function ($, Drupal) {
  Drupal.behaviors.jccTCToursPerson = {
    attach: function (context, settings) {

      $(document).on("click", ".tip-person-body .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(1) a').click();
      });

      $(document).on("click", ".tip-person-media .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(2) a').click();
      });

      $(document).on("click", ".tip-person-secondary-content .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(3) a').click();
      });

      $(document).on("click", ".tip-person-metadata .joyride-next-tip", function() {
        $('#edit-location').attr('open', '');
      });
    }
  };
})(jQuery, Drupal);
