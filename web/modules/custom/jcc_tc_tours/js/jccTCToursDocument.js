(function ($, Drupal) {
  Drupal.behaviors.jccTCToursDocument = {
    attach: function (context, settings) {

      $(document).click(function () {
        console.log("HELLO!");
        var clickedBtnID = $(this).attr('class'); // or var clickedBtnID = this.id
        console.log('You clicked on ' + clickedBtnID);
      });

      // From documents upload tip, open "Guides" panel under "Details" tab
      $(document).on("click", ".tip-document-documents .joyride-next-tip", function() {
        $('#edit-group-guides').attr('open', '');
      });

      // From guides tip, open "Related Links" tab
      $(document).on("click", ".tip-document-guides .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(1) a').click();
      });

      // From "Related Links" tip, open "Metadata" tab
      $(document).on("click", ".tip-document-secondary-content .joyride-next-tip", function() {
        $('.vertical-tabs__menu-item:eq(2) a').click();
      });
    }
  };
})(jQuery, Drupal);
