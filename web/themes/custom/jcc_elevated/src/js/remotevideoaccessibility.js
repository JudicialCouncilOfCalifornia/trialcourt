/**
* Remote video accessibility fix.
**/

(function ($, Drupal) {
    Drupal.behaviors.remotevideoaccessibilty = {
      attach: function attach(context, drupalSettings) {
        $(document).ready(function () {
          $(".remote-video-colorbox-launch-modal").attr( "tabindex", "0" );
        });
        $(document).on( "keypress", ".remote-video-colorbox-launch-modal", function(e) {
          if (e.which == 13 || e.which == 32){
            $(this).click();
          }
        });
      }
    }
})(jQuery, Drupal);
