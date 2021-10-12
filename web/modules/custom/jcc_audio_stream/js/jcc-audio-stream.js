/**
 * @file
 * JCC Audio Stream behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Behavior description.
   */
  Drupal.behaviors.jccAudioStream = {
    attach: function (context, settings) {
			$('.jcc-section__inner', context).once('add-audio-check').each (function() {
				$("a[data-radiomast-id]").each(function (index, element) {
          var UUID = element.dataset.radiomastId;
          // Run the checker on page load, then re-run every 5 seconds.
          loadStreamStatus(element, UUID);
          setInterval(function () {
            loadStreamStatus(element, UUID);
          }, 5000);
			});

      function loadStreamStatus(element, UUID) {
        var url = "https://audio-edge-8em5d.yyz.d.radiomast.io/";
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function () {
          if (this.readyState == 4) {
            if (this.status == 200) {
              $(element, UUID).addClass("radiomast--is-live");
            } else {
              $(element, UUID).removeClass("radiomast--is-live");
            }
          }
        };

        xhttp.timeout = 5000;
        xhttp.onerror = function (e) {
          $(element, UUID).removeClass("radiomast--is-live");
        };
        xhttp.ontimeout = function (e) {
          $(element, UUID).removeClass("radiomast--is-live");
        };

        xhttp.open("HEAD", url + UUID, true);
        xhttp.send();
      }
      })
    }
  }
} (jQuery, Drupal));
