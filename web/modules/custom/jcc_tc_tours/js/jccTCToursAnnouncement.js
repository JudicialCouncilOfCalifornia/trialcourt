(function ($, Drupal) {
  Drupal.behaviors.jccTCToursAnnouncement = {
    attach: function (context, settings) {
      // Open details elements that are part of the announcemnt tour.
      // Without this the tour items can't point to the elements inside.
      // @see jcc_tc_tourse.module for form alter attach to edit form.
      $('#toolbar-tab-tour button', context).once('jcTCToursAnnouncementVisibility').click(function () {
        $('#edit-group-visibility').attr('open', '');
        $('#edit-scheduler-settings').attr('open', '');
      });
    }
  };
})(jQuery, Drupal);
