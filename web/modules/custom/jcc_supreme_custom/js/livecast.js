(function (Drupal, once) {
  'use strict';

  /**
   * Loads livecast banner dynamically using HTML in JSON response.
   */
  Drupal.behaviors.jccSupremeLivecast = {
    attach: function (context) {
      once('jcc-supreme-livecast', '[data-jcc-livecast-url]', context).forEach(function (element) {
        fetch(element.dataset.jccLivecastUrl, {
          cache: 'no-store',
          credentials: 'same-origin'
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error('Livecast request failed: HTTP ' + response.status);
            }
            return response.json();
          })
          .then(function (data) {
            if (typeof data.html !== 'string') {
              throw new Error('Invalid livecast response: expected HTML.');
            }
            element.innerHTML = data.html;
            element.hidden = !data.html.trim();
            Drupal.attachBehaviors(element);
          })
          .catch(function (error) {
            console.error('Unable to load the Supreme Court livecast banner.', error);
          });
      });
    }
  };
})(Drupal, once);
