/**
 * @file
 * File links js file.
 */

(function($, Drupal) {

  'use strict';

  Drupal.behaviors.jccFilelink = {
    attach: function(context, settings) {

      var filelinks = [];
      $('a', context).each(function(el) {
        var url = '';
        if (typeof this.href == 'string') {
          url = this.href.toLowerCase();
        }
        // Look for filename suffixes.
        if (url.match(/(\.pdf|\.zip|\.doc|\.docx|\.xls|\.xlsx|\.ppt|\.pptx)/)) {
          filelinks.push(this);
        }
        $(filelinks).addClass('file');
        $(filelinks).attr('target', '_blank');
      });

      // Lightweight a11y labeling for remote video Colorbox launchers.
      $('div[data-remote-video-colorbox-modal]', context).each(function() {
        var $launcher = $(this);
        var $img = $launcher.find('img').first();

        $launcher.attr('aria-label', 'Launch video modal');

        if ($img.length) {
          $img.attr('alt', 'Launch video modal');
        }
      });

      if (!window.jccRemoteVideoColorboxFocusBound) {
        window.jccRemoteVideoColorboxFocusBound = true;

        $(document).on('cbox_complete', function() {
          var $content = $('#cboxLoadedContent');
          var $videoFrame = $content.find('iframe').first();

          if (!$content.length || !$videoFrame.length) {
            return;
          }

          if (!$content.is('[tabindex]')) {
            $content.attr('tabindex', '-1');
          }

          setTimeout(function() {
            $content.trigger('focus');
          }, 0);
        });
      }

    }
  }

})(jQuery, Drupal);
