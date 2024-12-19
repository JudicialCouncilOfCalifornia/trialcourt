/**
 * Real time Granicus event updates for Drupal events.
 * Core Granicus information are imported as Drupal events.
**/

(function ($, Drupal) {
  Drupal.behaviors.granicusevents = {
    attach: function attach(context, drupalSettings) {
      $(document).ready(function () {
        const events = document.querySelectorAll('.granicus');

        // If scheduled Drupal event loads, augment display with real-time Granicus information.
        if (events) {
          const parser = new DOMParser();
          const granicusFeed = document.querySelector('.granicus-region').getAttribute('data-granicus-feed');
          const today = new Date();
          const dateFormatter = {
            month: '2-digit',
            day: '2-digit',
            year: '2-digit'
          };
          const todayCustom = today.toLocaleDateString('en-US', dateFormatter);

          // Event live status & live captioning from Granicus feed.
          events.forEach(event => {
            if (!event.classList.contains('status-checked')) {
              let eventDate = event.getAttribute('data-event-date');

              // If today and working Granicus feed.
              if (eventDate === todayCustom && granicusFeed) {
                let eventType = event.getAttribute('data-event-type');

                // Get relevant real time information from Granicus feed.
                fetch(granicusFeed)
                  .then(response => response.text())
                  .then(data => {
                    const xml = parser.parseFromString(data, 'application/xml');
                    const granicusEvents = Array.from(xml.getElementsByTagName('event'));

                    // Parse for matching Granicus event.
                    granicusEvents.forEach(granicusEvent => {
                      let granicusDate = granicusEvent.getElementsByTagName('date')[0].textContent;
                      let granicusType = granicusEvent.getElementsByTagName('type')[0].textContent;

                      // Find matching Granicus event counterpart for scheduled Drupal event.
                      if (granicusDate === todayCustom && granicusType === eventType) {
                        // If live captioning is provided, add links inline.
                        let granicusCaptionsRaw = granicusEvent.getElementsByTagName('captioning')[0].textContent;
                        let granicusCaptions = granicusCaptionsRaw.split(';');
                        let granicusCaptionLinks = [];
                        granicusCaptions.forEach(granicusCaption => {
                          if (granicusCaption) {
                            let granicusCaptionLink = granicusCaption.split('|');
                            granicusCaptionLinks.push('<a href="' + granicusCaptionLink[1] + '" target="_blank">' + granicusCaptionLink[0] + '</a>');
                          }
                        });
                        if (granicusCaptionLinks.length > 2) {
                          granicusCaptionLinks = granicusCaptionLinks.join('or');
                        }
                        else {
                          granicusCaptionLinks = granicusCaptionLinks.toString();
                        }
                        event.querySelector('.granicus__captioning').innerHTML = 'View ' + granicusCaptionLinks + ' Captions';

                        // If Granicus event is running, update Drupal event status inline.
                        let granicusStatus = granicusEvent.getElementsByTagName('status')[0].textContent;
                        if (granicusStatus === 'Running') {
                          event.querySelector('.pill').textContent = 'live';
                        }
                      }
                    });
                  })
                  .catch(console.error);
              }

              // Flag to prevent repeated execution via AJAX.
              event.classList.add('status-checked');

              // Delay display until inline updates completed.
              setTimeout(() => {
                event.classList.add('visible');
              }, 1000);
            }
          });
        }
      });
    }
  }
})(jQuery, Drupal);
