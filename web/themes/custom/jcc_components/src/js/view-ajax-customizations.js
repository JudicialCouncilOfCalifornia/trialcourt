(function (Drupal, once) {
  Drupal.behaviors.viewAjaxCustomizations = {
    attach: function (context, settings) {
      // Listen for event triggered by Views AJAX.
      if (context !== document) {
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
        Drupal.announce(message, 'assertive');
      }
    }
  };
})(Drupal, once);
