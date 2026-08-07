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
          region.setAttribute('aria-live', level);
          region.setAttribute('aria-atomic', 'true');
          region.style.cssText = 'position:absolute;width:1px;height:1px;margin:-1px;padding:0;border:0;overflow:hidden;clip:rect(0 0 0 0);clip-path:inset(100%);white-space:nowrap;';
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
      
      // Listen for event triggered by Views AJAX.
      if (context !== document) {
        if (once('jcc-view-ajax-announce', context).length === 0) {
          return;
        }
        // Announce view update occurrence.
        let message = Drupal.t('The view has been updated.');
        // Check if the view has a results count.
        const resultsViews = [
          '.jcc-news-listing__content',
        ];
        const resultsView = context.querySelectorAll(resultsViews);
        if (resultsView.length > 0) {
          const resultsCount = resultsView[0].querySelector('.jcc-listing_result').textContent;
          if (resultsCount) {
            message = message + Drupal.t(' Now showing @count.', { '@count': resultsCount.trim()});
          }
        }
        announce(message, 'assertive');
      }
    }
  };
})(Drupal, once);
