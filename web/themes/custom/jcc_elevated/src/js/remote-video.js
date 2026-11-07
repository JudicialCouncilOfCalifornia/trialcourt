/**
* Remote video accessibility fix.
*
* Keyboard support for GLightbox remote video playback. Plyr
* already provides the support but was unused by GLightbox.
**/

(function (Drupal) {
  var playerSelector = '.gvideo.youtube-video .plyr, .gvideo.vimeo-video .plyr';
  var arrowKeys = ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'];
  var observing = false;
  var lastTrigger = null;

  // Binds keyboard support and accessibility features to a single video player container.
  function bindPlayer(container) {
    // Skip if the container is already bound to keyboard support.
    if (container.dataset.remoteVideoKeys) {
      return;
    }

    // Mark the container as bound to prevent duplicate bindings.
    container.dataset.remoteVideoKeys = 'true';

    // Ensure the container is focusable for keyboard users.
    if (!container.hasAttribute('tabindex')) {
      container.setAttribute('tabindex', '0');
    }

    // Handle keyboard events for the video container.
    var hideTimer;
    var holdPrevHover = false;
    container.addEventListener('keydown', function (event) {
      // Ensuring tab and arrow keys pertain to the Plyr controls.
      // Gallery support for videos not available at this time.
      var isSeekKey = arrowKeys.indexOf(event.key) !== -1;
      if (isSeekKey || event.key === 'Tab') {
        event.stopPropagation();
      }
      // Plyr only shows controls on mouse/touch, so reveal them for keyboard users.
      container.classList.remove('plyr--hide-controls');
      // Plyr skips hiding while controls.hover is true, so hold that flag during the seek.
      var controls = container.querySelector('.plyr__controls');
      if (controls && !hideTimer) {
        holdPrevHover = controls.hover;
        controls.hover = true;
      }
      // Set a new hide timer to hide controls after a certain amount of inactivity.
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

  // Binds all video players within the given root element.
  function bindAll(root) {
    if (root.matches && root.matches(playerSelector)) {
      bindPlayer(root);
    }
    if (root.querySelectorAll) {
      root.querySelectorAll(playerSelector).forEach(bindPlayer);
    }
  }

  // Sets focus on the current video player within the active GLightbox slide.
  // Matches GLightbox's autofocusVideos behavior, which only runs when autoplay is on.
  function focusCurrentPlayer() {
    var player = document.querySelector('.gslide.current ' + playerSelector);
    if (player && !player.contains(document.activeElement)) {
      player.focus({ preventScroll: true });
    }
  }

  // Binds space key to video play/pause.
  function spaceKeyPlayToggle(event) {
    if (event.key !== ' ' || event.defaultPrevented) {
      return;
    }
    // Toggle the current video's play state.
    var playButton = document.querySelector(
      '.gslide.current .gvideo.youtube-video [data-plyr="play"], .gslide.current .gvideo.vimeo-video [data-plyr="play"]'
    );
    if (playButton) {
      event.preventDefault();
      playButton.click();
    }
  }

  Drupal.behaviors.remotevideoaccessibilty = {
    attach: function attach(context) {
      // GLightbox builds its slides after attach runs, so watch for new players.
      if (!observing) {
        observing = true;

        // Space key triggers only video play/pause.
        document.addEventListener('keydown', spaceKeyPlayToggle);

        // Remember the last GLightbox trigger element to return focus to it when modal closes.
        document.addEventListener(
          'click',
          function (event) {
            var trigger = event.target.closest && event.target.closest('.glightbox');
            if (trigger) {
              lastTrigger = trigger;
            }
          },
          true
        );

        // Observe changes to the DOM to handle dynamically added or removed video players and GLightbox slides.
        new MutationObserver(function (mutations) {
          mutations.forEach(function (mutation) {
            // Modal open handling.
            mutation.addedNodes.forEach(function (node) {
              if (node.nodeType === 1) {
                bindAll(node);
              }
            });
            // Modal close handling.
            mutation.removedNodes.forEach(function (node) {
              // When modal closes, drop focus back to the last trigger element.
              if (node.nodeType === 1 && node.matches && node.matches('.glightbox-container') && lastTrigger) {
                lastTrigger.focus();
              }
            });
          });
        }).observe(document.body, { childList: true, subtree: true });

        // Ensure the current video player receives focus when a GLightbox slide becomes active.
        new MutationObserver(function (mutations) {
          mutations.forEach(function (mutation) {
            var target = mutation.target;
            if (target.matches && target.matches('.gslide.current')) {
              focusCurrentPlayer();
            }
          });
        }).observe(document.body, { attributes: true, attributeFilter: ['class'], subtree: true });
      }
      bindAll(context);
    }
  };
})(Drupal);
