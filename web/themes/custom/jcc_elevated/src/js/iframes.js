(function (Drupal, once) {
  Drupal.behaviors.iframes = {
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
        // Announce iframe loading events.
        window.addEventListener('load', () => {
          const iframes = once('jcc-iframes-handler', 'iframe', context);
          if (iframes.length > 0) {
            iframes.forEach(iframe => {
              let iframeTitle = iframe.getAttribute('title');
              let message = Drupal.t('An iframe has finished updating.');
              if (iframeTitle) {
                message = Drupal.t('"@title" iframe has finished updating.', { '@title': iframeTitle });
              }
              iframe.addEventListener('load', () => {
                Drupal.announce(message, 'assertive');
                console.log(message);
              });
            });
          }
        });
      });
    }
  };
})(Drupal, once);
