(function ($, Drupal) {
  Drupal.behaviors.jccTCToursAnnouncement = {
    attach: function (context, settings) {

      // Open the Visibility tab for the next step
      $(document).on("click", ".tip-announcement-body .joyride-next-tip", function() {
        $('#edit-group-visibility').attr('open', '');
      });

      // Open the Scheduler tab for the next step
      $(document).on("click", ".tip-announcement-visibility-page .joyride-next-tip", function() {
        $('#edit-scheduler-settings').attr('open', '');
      });

    }
  };
})(jQuery, Drupal);
