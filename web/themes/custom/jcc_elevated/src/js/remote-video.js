/**
* Remote video accessibility fix.
*
* Keyboard support for GLightbox remote video playback. Plyr
* already provides the support but was unused by GLightbox.
**/

(function (Drupal, once) {
  let playerSelector = '.gvideo.youtube-video .plyr, .gvideo.vimeo-video .plyr';
  let arrowKeys = ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'];
  let lastTrigger = null;
  let lastTriggerLabel = null;

  function bindPlayer(container) {
    if (container.dataset.remoteVideoKeys) {
      return;
    }
    container.dataset.remoteVideoKeys = 'true';
    if (!container.hasAttribute('tabindex')) {
      container.setAttribute('tabindex', '0');
    }

    let hideTimer;
    let holdPrevHover = false;
    container.addEventListener('keydown', function (event) {
      // Ensuring tab and arrow keys pertain to the Plyr controls.
      // Gallery support for videos not available at this time.
      let isSeekKey = arrowKeys.indexOf(event.key) !== -1;
      if (isSeekKey || event.key === 'Tab') {
        event.stopPropagation();
      }
      // Plyr only shows controls on mouse/touch, so reveal them for keyboard users.
      container.classList.remove('plyr--hide-controls');
      // Plyr skips hiding while controls.hover is true, so hold that flag during the seek.
      let controls = container.querySelector('.plyr__controls');
      if (controls && !hideTimer) {
        holdPrevHover = controls.hover;
        controls.hover = true;
      }
      clearTimeout(hideTimer);
      hideTimer = setTimeout(function () {
        hideTimer = null;
        if (controls) {
          controls.hover = holdPrevHover;
        }
        if (!container.classList.contains('plyr--paused') && !container.matches(':hover')) {
          container.classList.add('plyr--hide-controls');
        }
      }, 2000);
    });
  }

  function bindAll(root) {
    if (root.matches && root.matches(playerSelector)) {
      bindPlayer(root);
    }
    if (root.querySelectorAll) {
      root.querySelectorAll(playerSelector).forEach(bindPlayer);
    }
  }

  // Limits screen reader context.
  // Inert also removes the remote video's native UI from the tab order.
  function screenReaderScope() {
    // Focus on current slide only since gallery is not supported.
    document.querySelectorAll('.glightbox-container .gslide').forEach(function (slide) {
      if (slide.classList.contains('current')) {
        slide.removeAttribute('inert');
      } else {
        slide.setAttribute('inert', '');
      }
    });
    // Focus on Plyr controls only.
    document.querySelectorAll(playerSelector).forEach(function (player) {
      let active = !!player.closest('.gslide.current');
      Array.prototype.forEach.call(player.children, function (child) {
        if (child.classList.contains('plyr__controls')) {
          return;
        }
        if (active) {
          child.setAttribute('inert', '');
        } else {
          child.removeAttribute('inert');
        }
      });
    });
  }

  let region = null;
  let announceTimer = null;
  function ensureRegion() {
    if (!region || !document.body.contains(region)) {
      region = document.createElement('div');
      region.id = 'remote-video-announcements';
      region.className = 'visually-hidden';
      region.setAttribute('aria-live', 'polite');
      document.body.appendChild(region);
    }
    return region;
  }

  // The delay lets the live region settle before its text changes, so the message is announced.
  function announce(message) {
    if (!message) {
      return;
    }
    ensureRegion();
    clearTimeout(announceTimer);
    announceTimer = setTimeout(function () {
      region.textContent = message;
    }, 500);
  }

  // Limits tab context to Plyr controls and the GLightbox close button.
  // Prevents confusion with remote video's native UI until integration improvements.
  function trapTab(event) {
    if (event.key !== 'Tab') {
      return;
    }
    let modal = document.querySelector('.glightbox-container');
    if (!modal || !modal.contains(document.activeElement)) {
      return;
    }
    let player = modal.querySelector('.gslide.current ' + playerSelector);
    let close = modal.querySelector('.gclose');
    let visible = function (el) {
      return !el.disabled && el.getClientRects().length > 0;
    };
    let items = player
      ? Array.prototype.filter.call(
          player.querySelectorAll('.plyr__controls button, .plyr__controls input'),
          visible
        )
      : [];
    if (close && visible(close)) {
      items.push(close);
    }
    if (!items.length) {
      event.preventDefault();
      return;
    }
    let index = items.indexOf(document.activeElement);
    let last = items.length - 1;
    let next;
    if (event.shiftKey) {
      next = index <= 0 ? last : index - 1;
    } else {
      next = index === -1 || index === last ? 0 : index + 1;
    }
    event.preventDefault();
    items[next].focus();
  }

  function spaceKeyOpenModal(event) {
    if (event.key !== ' ' || event.defaultPrevented) {
      return;
    }
    let trigger = event.target.closest && event.target.closest('a.glightbox');
    if (trigger) {
      event.preventDefault();
      trigger.click();
    }
  }

  Drupal.behaviors.remoteVideoAccessibility = {
    attach: function attach(context) {
      // Only initialize once per page load.
      if (!once('remote-video-accessibility', 'body').length) {
        return;
      }

      document.addEventListener(
        'ready',
        function (event) {
          // Ensure the current slide's player has focus within the GLightbox modal.
          let player = event.target;
          let dialog = player.closest('.glightbox-container');
          setTimeout(function () {
            if (dialog) {
              dialog.focus({ preventScroll: true });
            }
          }, 500);
        },
        true
      );

      // Tab and space keys handling.
      document.addEventListener('keydown', trapTab, true);
      document.addEventListener('keydown', spaceKeyOpenModal);

      // Remember last trigger element and its label when a GLightbox link is clicked.
      // Used later to return focus to the trigger element after modal is closed.
      document.addEventListener(
        'click',
        function (event) {
          let trigger = event.target.closest && event.target.closest('.glightbox');
          if (trigger) {
            lastTrigger = trigger;
            lastTriggerLabel = trigger.getAttribute('aria-label') || trigger.textContent.trim() || trigger.title || '';
          }
        },
        true
      );

      // GLightbox builds its slides after attach runs, so watch for new players and slide changes.
      new MutationObserver(function (mutations) {
        let needsScope = false;
        mutations.forEach(function (mutation) {
          // Check if any added or modified nodes require scoping for screen readers.
          if (mutation.type === 'attributes') {
            if (mutation.target.matches && mutation.target.matches('.gslide.current')) {
              needsScope = true;
            }
            return;
          }
          mutation.addedNodes.forEach(function (node) {
            if (node.nodeType === 1) {
              // Reset announce region for every new modal session.
              if (node.matches && node.matches('.glightbox-container') && region) {
                region.remove();
                region = null;
              }
              // Bind all necessary event handlers.
              bindAll(node);
              // Initialize any new GLightbox players within the added node.
              needsScope = true;
            }
          });
          mutation.removedNodes.forEach(function (node) {
            // Handle focus return when closing modal.
            if (node.nodeType === 1 && node.matches && node.matches('.glightbox-container') && lastTrigger) {
              lastTrigger.focus();
              announce(lastTriggerLabel ? 'Returning focus to link for ' + lastTriggerLabel : 'Returning focus to link');
            }
          });
        });
        // Apply screen reader scoping if needed after processing mutations.
        if (needsScope) {
          screenReaderScope();
        }
      }).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });

      // Bind all necessary event handlers and apply initial screen reader scoping.
      bindAll(context);
      screenReaderScope();
    }
  };
})(Drupal, once);
