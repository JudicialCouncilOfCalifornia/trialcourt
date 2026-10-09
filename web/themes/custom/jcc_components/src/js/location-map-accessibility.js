/**
 * Keep visual-only maps out of the tab order, including async map popups.
 */
(function (Drupal, once) {
  'use strict';

  const observers = new WeakMap();
  const selector = '[data-location-map-visual-only]';
  const preventMapFocus = function (event) {
    // Providers can explicitly focus the map after mouse zoom or popup actions.
    // Focus inside aria-hidden content would expose it to assistive technology.
    event.target.blur();
  };

  Drupal.behaviors.locationMapAccessibility = {
    attach: function (context) {
      once('location-map-accessibility', selector, context).forEach(function (map) {
        const removeTabStops = function () {
          map.querySelectorAll('a[href], button, input, select, textarea, iframe, [tabindex], [contenteditable]').forEach(function (element) {
            if (element.getAttribute('tabindex') !== '-1') {
              element.setAttribute('tabindex', '-1');
            }
          });
        };

        removeTabStops();
        map.addEventListener('focus', preventMapFocus, true);
        // Providers create markers, controls and popup links after attachment.
        const observer = new MutationObserver(removeTabStops);
        observer.observe(map, {
          childList: true,
          subtree: true,
          attributes: true,
          attributeFilter: ['tabindex', 'href', 'contenteditable']
        });
        observers.set(map, observer);
      });
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        once.remove('location-map-accessibility', selector, context).forEach(function (map) {
          observers.get(map).disconnect();
          observers.delete(map);
          map.removeEventListener('focus', preventMapFocus, true);
        });
      }
    }
  };
})(Drupal, once);
