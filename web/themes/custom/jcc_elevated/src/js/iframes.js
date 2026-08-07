(function (Drupal, once) {
  Drupal.behaviors.iframes = {
    attach: function (context, drupalSettings) {
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
                announce(message, 'assertive');
              });
            });
          }
        });
      });
    }
  };
})(Drupal, once);
