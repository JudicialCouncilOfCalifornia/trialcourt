/**
 * @file
 * Alter map links to use maps protocol on desktop.
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.jccCkeditorLink = {
    attach: function (context, settings) {

      // Find all google map links.
      let links = document.querySelectorAll('a[href*="maps.google.com"]');
      links.forEach(function(link) {

        if (!link.classList.contains('js-maplink-processed')) {
          link.classList.add('js-maplink-processed');

          let appleMacOs = (/MacIntel|Mac OS X|Macintosh|AppleWebKit|iPhone|iPad|iPod/i.test(navigator.userAgent));
          // let mobileDevice = (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent));

          // Assign the link to a URL object.
          const linkObj = new URL(link);

          // If we are on a PC (not an apple) we want to skip out and not change
          // the protocol of the map link.
          if (!appleMacOs) {
            return;
          }

          // If the link starts with https or http.
          if ((linkObj.protocol === 'https:') || (linkObj.protocol === 'http:')) {

            // Reassign the protocol to maps.
            linkObj.protocol = 'maps:';
            link.href = linkObj.toString();

            // Set the target to try to open the system app before the website.
            link.target = '_system';
          }
        }
      });

    }
  };
})(jQuery, Drupal);
