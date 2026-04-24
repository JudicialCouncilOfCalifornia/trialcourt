(function (Drupal, once) {
  Drupal.behaviors.opinionsA11y = {
    attach: function (context) {
      once('opinions-a11y-announcement', '[data-opinions-results-live]', context).forEach(function (liveRegion) {
        var announcement = liveRegion.getAttribute('data-results-announcement');
        if (!announcement) {
          return;
        }

        // Force a text update so SR announces refreshed result count.
        liveRegion.textContent = '';
        window.setTimeout(function () {
          liveRegion.textContent = announcement;
        }, 100);
      });
    }
  };
})(Drupal, once);
