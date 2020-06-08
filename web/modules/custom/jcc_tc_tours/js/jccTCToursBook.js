(function ($, Drupal) {
  Drupal.behaviors.jccTCToursBook = {
    attach: function (context, settings) {

      $(document).on("click", ".tip-book-body .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(1) a').click();
      });

      $(document).on("click", ".tip-book-media .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(2) a').click();
      });

      $(document).on("click", ".tip-book-secondary-content .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(3) a').click();
      });

      $(document).on("click", ".tip-book-metadata .joyride-next-tip", function() {
        $('#edit-book').attr('open', '');
      });
    }
  };
})(jQuery, Drupal);
