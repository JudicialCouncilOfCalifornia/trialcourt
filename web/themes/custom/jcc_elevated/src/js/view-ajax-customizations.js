(function (Drupal) {
  Drupal.behaviors.viewAjaxCustomizations = {
    attach: function (context, drupalSettings) {
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
        // Announce view region update occurrence.
        if (context !== document) {
          const resultsView = document.querySelectorAll('.view-results');
          const resultsCount = resultsView[0].querySelector('.cluster .views-results_content-header').textContent;
          let message = Drupal.t('The view has been updated.');
          if (resultsCount) {
            message = Drupal.t('The view has been updated. Now showing @count.', { '@count': resultsCount.trim()});
          }
          Drupal.announce(message, 'assertive');
        }
      });
    }
  };
})(Drupal);
