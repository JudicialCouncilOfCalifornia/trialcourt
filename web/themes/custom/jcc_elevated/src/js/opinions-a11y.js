(function (Drupal) {
  Drupal.behaviors.opinionsA11y = {
    attach: function (context) {
      var liveRegions = [];

      if (context.matches && context.matches('[data-opinions-results-live]')) {
        liveRegions.push(context);
      }

      if (context.querySelectorAll) {
        liveRegions = liveRegions.concat(Array.prototype.slice.call(
          context.querySelectorAll('[data-opinions-results-live]')
        ));
      }

      liveRegions.forEach(function (liveRegion) {
        if (liveRegion.getAttribute('data-opinions-a11y-processed') === 'true') {
          return;
        }
        liveRegion.setAttribute('data-opinions-a11y-processed', 'true');

        var announcement = liveRegion.getAttribute('data-results-announcement');
        if (!announcement) {
          return;
        }

        // Force a text update after load so screen readers announce changes.
        liveRegion.textContent = '';
        window.setTimeout(function () {
          liveRegion.textContent = announcement;
        }, 100);
      });
    }
  };
})(Drupal);
