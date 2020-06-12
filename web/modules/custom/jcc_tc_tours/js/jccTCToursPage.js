(function ($, Drupal) {
  Drupal.behaviors.jccTCToursPage = {
    attach: function (context, settings) {

      $(document).on("click", ".tip-page-body .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(1) a').click();
      });

      $(document).on("click", ".tip-page-media .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(2) a').click();
      });

      $(document).on("click", ".tip-page-secondary-content .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(3) a').click();
      });

      $(document).on("click", ".tip-page-metadata .joyride-next-tip", function() {
        $('#edit-page').attr('open', '');
      });
    }
  };
})(jQuery, Drupal);
