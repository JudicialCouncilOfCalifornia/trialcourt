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

  // Media bundles that represent video. Internal links to these are
  // data-entity-type="media" and share the same courtyard icon selector as
  // documents, so they need their own icon (see jcc-custom-filelink.css) and
  // their own screen reader label.
  var VIDEO_BUNDLES = [
    'remote_video',
    'oembed_video',
    'akamai_video',
    'boxcast_stream',
    'video',
    'video_embed'
  ];

  /**
   * Append visually hidden text conveying the linked media type.
   *
   * The type icon on these links is drawn with a CSS ::after pseudo-element,
   * which is not part of the accessibility tree. Without this, screen readers
   * only announce the link text and never convey whether the link points to a
   * document or a video.
   *
   * @param {HTMLAnchorElement} link
   *   The media/file link element.
   * @param {string} label
   *   The human readable media type, e.g. "PDF Document" or "Video".
   */
  function announceMediaType(link, label) {
    // Only label each link once, even if behaviors re-attach (e.g. via AJAX).
    if (link.getAttribute('data-media-a11y')) {
      return;
    }
    link.setAttribute('data-media-a11y', 'true');

    // Respect an author-provided accessible name; aria-label/aria-labelledby
    // already override the link content for assistive tech, so appended hidden
    // text would be ignored anyway.
    if (link.getAttribute('aria-label') || link.getAttribute('aria-labelledby')) {
      return;
    }

    if (!label) {
      return;
    }

    // Leading space keeps the announcement from running into the link text.
    $(link).append('<span class="usa-sr-only"> ' + label + '</span>');
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
          announceMediaType(this, DOCUMENT_TYPES[match[1]]);
        }
        $(filelinks).addClass('file');
        $(filelinks).attr('target', '_blank');
      });

      // Internal video media links carry no file extension, so classify them by
      // media bundle. Some references render without the data-entity-bundle
      // attribute (e.g. a second link to the same /media/NNN entity); those
      // still resolve to the canonical media route, which in body content is a
      // remote video (documents link straight to /system/files instead).
      $('a[data-entity-type="media"]', context).each(function() {
        var bundle = this.getAttribute('data-entity-bundle');
        var isVideo = bundle
          ? VIDEO_BUNDLES.indexOf(bundle) !== -1
          : /^\/media\/\d+$/.test(this.pathname || '');

        if (isVideo) {
          // Class drives the play-button icon in jcc-custom-filelink.css.
          $(this).addClass('jcc-media-video');
          announceMediaType(this, 'Video');
        }
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
