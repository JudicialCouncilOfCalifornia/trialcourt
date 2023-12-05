/**
* Adds tabulation accessibility event to colorbox triggers.
**/

(function (Drupal) {
    Drupal.behaviors.colorboxAccessibility = {
      attach: function attach(context, settings) {
        jQuery('.remote-video-colorbox-launch-modal').on('keyup', function (event) {
          if (event.originalEvent.code === 'Enter') {
            jQuery(this).trigger('click');
          }
        });
      }
    };
  })(Drupal);
