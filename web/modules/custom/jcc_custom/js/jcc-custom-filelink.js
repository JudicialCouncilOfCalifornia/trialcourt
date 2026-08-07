/**
 * @file
 * File links js file.
 */

(function($, Drupal) {

  'use strict';

  // Map a file extension to a human readable document type. The label is read
  // by screen readers after the link text, e.g. "my-form.pdf, PDF Document".
  // Note: .docx/.xlsx/.pptx resolve to the same family label as .doc/.xls/.ppt,
  // so the ordered detection regex below never mislabels across families.
  var DOCUMENT_TYPES = {
    pdf: 'PDF Document',
    zip: 'ZIP Archive',
    doc: 'Word Document',
    docx: 'Word Document',
    xls: 'Excel Spreadsheet',
    xlsx: 'Excel Spreadsheet',
    ppt: 'PowerPoint Presentation',
    pptx: 'PowerPoint Presentation'
  };

  /**
   * Add screen-reader-only text announcing the document type on a file link.
   *
   * The document icon on these links is drawn with a CSS ::after pseudo-element,
   * which is not part of the accessibility tree. Without this, screen readers
   * only announce the file name and never convey that the link is a document.
   *
   * @param {HTMLAnchorElement} link
   *   The file link element.
   * @param {string} extension
   *   The detected file extension, without the leading dot.
   */
  function announceDocumentType(link, extension) {
    // Only label each link once, even if behaviors re-attach (e.g. via AJAX).
    if (link.getAttribute('data-doc-a11y')) {
      return;
    }
    link.setAttribute('data-doc-a11y', 'true');

    // Respect an author-provided accessible name; aria-label/aria-labelledby
    // already override the link content for assistive tech, so appended hidden
    // text would be ignored anyway.
    if (link.getAttribute('aria-label') || link.getAttribute('aria-labelledby')) {
      return;
    }

    var documentType = DOCUMENT_TYPES[extension];
    if (!documentType) {
      return;
    }

    // Leading space keeps the announcement from running into the file name.
    $(link).append('<span class="usa-sr-only"> ' + documentType + '</span>');
  }

  Drupal.behaviors.jccFilelink = {
    attach: function(context, settings) {

      var filelinks = [];
      $('a', context).each(function(el) {
        var url = '';
        if (typeof this.href == 'string') {
          url = this.href.toLowerCase();
        }
        // Look for filename suffixes.
        var match = url.match(/\.(pdf|zip|docx|doc|xlsx|xls|pptx|ppt)/);
        if (match) {
          filelinks.push(this);
          announceDocumentType(this, match[1]);
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
