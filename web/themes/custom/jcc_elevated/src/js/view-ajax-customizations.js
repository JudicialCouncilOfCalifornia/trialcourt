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

      function docReady(fn) {
        // See if DOM is already available.
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
          // Call on next available tick.
          setTimeout(fn, 1);
        } else {
          document.addEventListener('DOMContentLoaded', fn);
        }
      }

      docReady(function() {
        // Listen for event triggered by Views AJAX.
        if (context !== document) {
          // Announce view update occurrence.
          let message = Drupal.t('The view has been updated.');

          // Check if the view has a results count.
          const resultsView = context.querySelectorAll('.view-results');
          if (resultsView.length > 0) {
            const resultsCount = resultsView[0].querySelector('.cluster .views-results_content-header').textContent;
            if (resultsCount) {
              message = message + Drupal.t(' Now showing @count.', { '@count': resultsCount.trim()});
            }
          }

          announce(message, 'assertive');
        }
      });
    }
  };
})(Drupal, once);
