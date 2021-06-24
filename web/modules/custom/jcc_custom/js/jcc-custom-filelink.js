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

    }
  }

})(jQuery, Drupal);
