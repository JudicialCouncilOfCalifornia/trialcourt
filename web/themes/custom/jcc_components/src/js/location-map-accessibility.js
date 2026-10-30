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
          let orderFrame;
          const orderMarkers = function () {
            // Use rendered map pixels so markers on the same visual row are
            // visited left-to-right, even if their latitudes differ slightly.
            const focused = document.activeElement;
            const markers = map.mapMarkers.filter(function (marker) {
              return marker.getElement();
            }).slice().sort(function (a, b) {
              const first = map.leafletMap.latLngToLayerPoint(a.getLatLng());
              const second = map.leafletMap.latLngToLayerPoint(b.getLatLng());
              return first.y - second.y || first.x - second.x;
            });
            markers.forEach(function (marker, index) {
              const icon = marker.getElement();
              const parent = icon.parentNode;
              const current = parent.querySelectorAll('.leaflet-marker-icon')[index];
              if (current !== icon) {
                parent.insertBefore(icon, current || null);
              }
            });
            if (wrapper.contains(focused) && document.activeElement !== focused) {
              focused.focus({ preventScroll: true });
            }
          };
          const scheduleMarkerOrder = function () {
            if (state.active) {
              cancelAnimationFrame(orderFrame);
              orderFrame = requestAnimationFrame(orderMarkers);
            }
          };
          state.cleanup.push(function () {
            cancelAnimationFrame(orderFrame);
          });
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
            scheduleMarkerOrder();
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

            const prepareMarker = function () {
              const icon = marker.getElement();
              if (!icon) {
                return;
              }
              icon.setAttribute('tabindex', '0');
              icon.setAttribute('role', 'button');
              icon.setAttribute('aria-haspopup', 'dialog');
              icon.setAttribute('aria-label', Drupal.t('Show location information for @location', { '@location': label }));
              icon.setAttribute('aria-expanded', marker.isPopupOpen() ? 'true' : 'false');
              if (!marker.isPopupOpen()) {
                icon.removeAttribute('aria-controls');
              }
            };

            const focusPopup = function () {
              const popup = marker.getPopup();
              const element = popup && popup.getElement();
              const firstControl = element && element.querySelector('a[href], button');
              if (firstControl) {
                firstControl.focus();
              }
            };

            const onOpen = function () {
              const popup = marker.getPopup().getElement();
              popup.removeAttribute('inert');
              popup.removeAttribute('aria-hidden');
              popup.id = id;
              popup.setAttribute('role', 'dialog');
              popup.setAttribute('aria-label', Drupal.t('Location information for @location', { '@location': label }));
              marker.getElement().setAttribute('aria-expanded', 'true');
              marker.getElement().setAttribute('aria-controls', id);

              const close = popup.querySelector('.leaflet-popup-close-button');
              if (close) {
                close.setAttribute('role', 'button');
                close.setAttribute('aria-label', Drupal.t('Close location information for @location', { '@location': label }));
                // The close control is visually above the heading and phone.
                // Place it first in DOM order instead of using positive tabindex.
                popup.insertBefore(close, popup.firstChild);
              }
              focusPopup();
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
              marker.openPopup();
              focusPopup();
            };

            const onFocus = function (event) {
              if (event.target === marker.getElement()) {
                // A zoomed or panned map can leave keyboard targets offscreen.
                // Bring the focused marker and its ring inside the map viewport.
                keepFocusedLocationVisible();
              }
            };

            marker.on('add', prepareMarker);
            marker.on('add', scheduleMarkerOrder);
            marker.on('popupopen', onOpen);
            marker.on('popupclose', onClose);
            wrapper.addEventListener('keydown', onKeyDown, true);
            wrapper.addEventListener('focus', onFocus, true);
            prepareMarker();
            scheduleMarkerOrder();
            if (marker.isPopupOpen()) {
              onOpen();
            }
            state.cleanup.push(function () {
              marker.off('add', prepareMarker);
              marker.off('add', scheduleMarkerOrder);
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

            if (event.key === 'Escape') {
              event.preventDefault();
              event.stopPropagation();
              marker.closePopup();
              marker.getElement().focus();
            }
            else if (event.key === 'Tab') {
              const controls = Array.from(popup.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]')).filter(function (element) {
                return element.tabIndex >= 0 && element.getClientRects().length;
              });
              event.preventDefault();
              event.stopPropagation();
              if (controls.length) {
                const index = controls.indexOf(document.activeElement);
                const next = (index + (event.shiftKey ? -1 : 1) + controls.length) % controls.length;
                controls[next].focus();
              }
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
