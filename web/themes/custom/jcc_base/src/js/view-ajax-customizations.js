(function (Drupal, once) {
  Drupal.behaviors.viewAjaxCustomizations = {
    attach: function (context, settings) {
      function announce(message, priority) {
        if (!message) {
          return;
        }

        const level = priority === 'assertive' ? 'assertive' : 'polite';
        const id = 'jcc-announce-' + level;
        let region = document.getElementById(id);

        if (!region) {
          region = document.createElement('div');
          region.id = id;
          region.className = 'visually-hidden';
          region.setAttribute('aria-live', level);
          region.setAttribute('aria-atomic', 'true');
          (document.body || document.documentElement).appendChild(region);
        }

        // Clear and repopulate only the message text to avoid duplicate announcements.
        region.textContent = '';
        window.setTimeout(function () {
          region.textContent = message;
        }, 50);
      }

      function announceMessage(customMsg) {
        // Announce view update occurrence.
        let message = Drupal.t('The view has been updated.');

        // If any context can be provided.
        if (customMsg) {
          message = customMsg;
        }

        announce(message, 'assertive');
      }

      // Listen for event triggered by Views AJAX.
      // Anonymous user support only.
      if (context !== document) {
        if (context.className.includes('fc-view')) {
          // If calendar view is in use.
          const calendar = document.querySelector('.js-drupal-fullcalendar');
          // Announce when Ajax is triggered by any control.
          // This also prevents the announce on page load.
          const ctrlSelectors = [
            'button',
            '.fc-day-header a',
            '.fc-list-heading .fc-widget-header a'
          ];
          const calendarControls = calendar.querySelectorAll(ctrlSelectors.join(', '));
          for (let control of calendarControls) {
            control.addEventListener('click', (event) => {
              announceMessage(Drupal.t('The calendar view has been updated.'));
            });
          }
        }
        else {
          let message = null;
          const viewElements = context.children;
          const headingSelectors = [
            'view__header',
          ];
          for (let element of viewElements) {
            if (element.className.includes(headingSelectors.join(', '))) {
              if (element && element.textContent) {
                message = Drupal.t(element.textContent + ' view has been updated.');
                break;
              }
            }
          }

          announceMessage(message);
        }
      }
    }
  };
})(Drupal, once);
