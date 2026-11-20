(function (Drupal, once) {
  function announce(message, priority, delay) {
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
    }, delay || 50);
  }

  Drupal.behaviors.viewAjaxCustomizations = {
    attach: function (context, settings) {
      // Listen for event triggered by Views AJAX.
      // Anonymous user support only.
      if (context !== document) {
        // Announce view update occurrence.
        let message = Drupal.t('The view has been updated.');

        // Check if the view has a results count.
        // Should be the one selector pattern but can list others.
        const resultsViews = [
          '.view-results',
        ];
        const resultsView = context.querySelectorAll(resultsViews.join(', '));
        if (resultsView.length > 0) {
          const resultsCount = resultsView[0].querySelector('.cluster .views-results_content-header').textContent;
          if (resultsCount) {
            message = message + Drupal.t(' Now showing @count.', { '@count': resultsCount.trim()});
          }
        }
        announce(message, 'assertive');
      }
    }
  };

  // Move focus to the results heading, then announce the results summary
  // via aria-live, once per page load (and again if the view is replaced
  // via AJAX). Screen readers only read the document title on a normal
  // page load, so without this the results go unannounced until the user
  // manually navigates down to them.
  Drupal.behaviors.searchResultsAnnounce = {
    attach: function (context, settings) {
      once('search-results-focus', '[data-search-results-heading]', context).forEach(function (heading) {
        heading.focus();
      });

      // Delayed so it doesn't talk over the heading announced by the focus move above.
      once('search-results-announce', '[data-search-results-announce]', context).forEach(function (element) {
        announce(element.textContent.trim(), 'polite', 600);
      });
    }
  };
})(Drupal, once);
