(function (Drupal) {
  Drupal.behaviors.viewAjaxCustomizations = {
    attach: function (context, settings) {
      if (context !== document) {
        // Announce view region update occurrence.
        const resultsView = document.querySelectorAll('.view-results');
        const resultsCount = resultsView[0].querySelector('.cluster .views-results_content-header').textContent;
        let message = Drupal.t('The view has been updated.');
        if (resultsCount) {
          message = Drupal.t('The view has been updated. Now showing @count.', { '@count': resultsCount.trim()});
        }
        Drupal.announce(message, 'assertive');
      }
    }
  };
})(Drupal);
