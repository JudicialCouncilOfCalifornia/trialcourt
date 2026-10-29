/**
 * @file
 * Keyboard access for Leaflet location markers and their nonmodal popups.
 */
(function (Drupal, once) {
  'use strict';

  const selector = '[data-location-map-accessible]';
  const states = new WeakMap();
  let popupId = 0;

  Drupal.behaviors.locationMapAccessibility = {
    attach: function (context) {
      once('location-map-accessibility', selector, context).forEach(function (wrapper) {
        const map = Drupal.geolocation.getMapById(wrapper.id);
        if (!map || map.type !== 'leaflet') {
          return;
        }

        const state = { active: true, cleanup: [] };
        states.set(wrapper, state);
        map.addPopulatedCallback(function () {
          if (!state.active) {
            return;
          }

          map.container.attr({ role: 'region', 'aria-label': Drupal.t('Court locations map') });
          let zooming = false;
          let focusFrame;
          const keepFocusedLocationVisible = function () {
            const marker = map.mapMarkers.find(function (item) {
              const popup = item.getPopup();
              const element = popup && popup.getElement();
              return item.getElement() === document.activeElement ||
                (item.isPopupOpen() && element && element.contains(document.activeElement));
            });
            if (marker && !zooming) {
              // An earlier popup auto-pan may still be animating after zoomend.
              // Stop it before applying the final focused location position.
              map.leafletMap.stop();
              if (marker.getElement() === document.activeElement) {
                map.leafletMap.panInside(marker.getLatLng(), {
                  padding: [45, 60],
                  animate: false
                });
              }
              if (marker.isPopupOpen()) {
                // Keep the existing popup DOM (and focus) while bringing its
                // controls inside the map, rather than rebuilding its content.
                const popup = marker.getPopup().getElement().getBoundingClientRect();
                const container = map.container.get(0).getBoundingClientRect();
                const x = Math.max(0, popup.right - container.right + 8) -
                  Math.max(0, container.left + 8 - popup.left);
                const y = Math.max(0, popup.bottom - container.bottom + 8) -
                  Math.max(0, container.top + 8 - popup.top);
                if (x || y) {
                  map.leafletMap.panBy([x, y], { animate: false });
                }
              }
            }
          };
          const onZoomStart = function () {
            zooming = true;
          };
          const onZoomEnd = function () {
            zooming = false;
            // Zoom animation and popup auto-pan can overwrite a focus-time pan.
            // Recheck the current focus after the provider finishes zooming.
            cancelAnimationFrame(focusFrame);
            focusFrame = requestAnimationFrame(keepFocusedLocationVisible);
          };
          map.leafletMap.on('zoomstart', onZoomStart);
          map.leafletMap.on('zoomend', onZoomEnd);
          state.cleanup.push(function () {
            cancelAnimationFrame(focusFrame);
            map.leafletMap.off('zoomstart', onZoomStart);
            map.leafletMap.off('zoomend', onZoomEnd);
          });

          map.addMarkerAddedCallback(function (marker) {
            if (!state.active || !marker.locationWrapper) {
              return;
            }

            const title = marker.locationWrapper.find('.location-content .usa-card__heading a').text().trim();
            const label = title || Drupal.t('Court location');
            const id = 'location-map-popup-' + (++popupId);
            let keyboardOpening = false;

            const prepareMarker = function () {
              const icon = marker.getElement();
              if (!icon) {
                return;
              }
              icon.setAttribute('tabindex', '0');
              icon.setAttribute('role', 'button');
              icon.setAttribute('aria-label', Drupal.t('Show location information for @location', { '@location': label }));
              icon.setAttribute('aria-expanded', marker.isPopupOpen() ? 'true' : 'false');
              if (!marker.isPopupOpen()) {
                icon.removeAttribute('aria-controls');
              }
            };

            const focusPopup = function () {
              const popup = marker.getPopup();
              const element = popup && popup.getElement();
              const firstLink = element && element.querySelector('.leaflet-popup-content a[href]');
              if (firstLink) {
                firstLink.focus();
              }
            };

            const onOpen = function () {
              const popup = marker.getPopup().getElement();
              popup.removeAttribute('inert');
              popup.removeAttribute('aria-hidden');
              popup.id = id;
              popup.setAttribute('role', 'region');
              popup.setAttribute('aria-label', Drupal.t('Location information for @location', { '@location': label }));
              marker.getElement().setAttribute('aria-expanded', 'true');
              marker.getElement().setAttribute('aria-controls', id);

              const close = popup.querySelector('.leaflet-popup-close-button');
              if (close) {
                close.setAttribute('role', 'button');
                close.setAttribute('aria-label', Drupal.t('Close location information for @location', { '@location': label }));
              }
              if (keyboardOpening) {
                focusPopup();
              }
            };

            const onClose = function (event) {
              const popup = event.popup.getElement();
              const restoreFocus = popup && popup.contains(document.activeElement);
              // Leaflet fires popupclose before clearing its open flag.
              const icon = marker.getElement();
              if (icon) {
                icon.setAttribute('aria-expanded', 'false');
                icon.removeAttribute('aria-controls');
              }
              if (restoreFocus && marker.getElement()) {
                marker.getElement().focus();
              }
              // Leaflet leaves closed popups in the DOM during its fade-out.
              // Their links must stop receiving focus immediately.
              if (popup) {
                popup.setAttribute('inert', '');
                popup.setAttribute('aria-hidden', 'true');
              }
            };

            // Capture activation before Leaflet's built-in Enter handler to avoid
            // opening then immediately toggling the same popup closed.
            const onKeyDown = function (event) {
              if (event.target !== marker.getElement() || !['Enter', ' '].includes(event.key)) {
                return;
              }
              event.preventDefault();
              event.stopImmediatePropagation();
              keyboardOpening = true;
              marker.openPopup();
              focusPopup();
              keyboardOpening = false;
            };

            const onFocus = function (event) {
              if (event.target === marker.getElement()) {
                // A zoomed or panned map can leave keyboard targets offscreen.
                // Bring the focused marker and its ring inside the map viewport.
                keepFocusedLocationVisible();
              }
            };

            marker.on('add', prepareMarker);
            marker.on('popupopen', onOpen);
            marker.on('popupclose', onClose);
            wrapper.addEventListener('keydown', onKeyDown, true);
            wrapper.addEventListener('focus', onFocus, true);
            prepareMarker();
            if (marker.isPopupOpen()) {
              onOpen();
            }
            state.cleanup.push(function () {
              marker.off('add', prepareMarker);
              marker.off('popupopen', onOpen);
              marker.off('popupclose', onClose);
              wrapper.removeEventListener('keydown', onKeyDown, true);
              wrapper.removeEventListener('focus', onFocus, true);
            });
          });

          const onPopupKeyDown = function (event) {
            const popup = event.target.closest('.leaflet-popup');
            if (!popup) {
              return;
            }
            const marker = map.mapMarkers.find(function (item) {
              return item.isPopupOpen() && item.getPopup().getElement() === popup;
            });
            if (!marker) {
              return;
            }

            const firstLink = popup.querySelector('.leaflet-popup-content a[href]');
            if (event.key === 'Escape') {
              event.preventDefault();
              event.stopPropagation();
              marker.closePopup();
              marker.getElement().focus();
            }
            else if (event.key === 'Tab' && event.shiftKey && event.target === firstLink) {
              // Return to the opener rather than the last marker in DOM order.
              event.preventDefault();
              marker.getElement().focus();
            }
            else if (event.key === ' ' && event.target.matches('.leaflet-popup-close-button')) {
              event.preventDefault();
              event.target.click();
            }
          };
          wrapper.addEventListener('keydown', onPopupKeyDown);
          state.cleanup.push(function () {
            wrapper.removeEventListener('keydown', onPopupKeyDown);
          });
        });
      });
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        once.remove('location-map-accessibility', selector, context).forEach(function (wrapper) {
          const state = states.get(wrapper);
          if (state) {
            state.active = false;
            state.cleanup.forEach(function (cleanup) { cleanup(); });
            states.delete(wrapper);
          }
        });
      }
    }
  };
})(Drupal, once);
