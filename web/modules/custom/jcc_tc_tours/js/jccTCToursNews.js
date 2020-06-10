(function ($, Drupal) {
  Drupal.behaviors.jccTCToursNews = {
    attach: function (context, settings) {

      $(document).on("click", ".tip-news-body .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(1) a').click();
      });

      $(document).on("click", ".tip-news-media .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(2) a').click();
      });

      $(document).on("click", ".tip-news-secondary-content .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(3) a').click();
      });

      $(document).on("click", ".tip-news-metadata .joyride-next-tip", function() {
        $('#edit-news').attr('open', '');
      });
    }
  };
})(jQuery, Drupal);
