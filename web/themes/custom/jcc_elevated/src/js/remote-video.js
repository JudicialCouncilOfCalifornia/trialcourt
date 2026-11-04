/**
* Remote video accessibility fix.
*
* Keyboard support for GLightbox remote videoplayback. Plyr handles
* already provides the support but was unused by GLightbox.
**/

(function (Drupal) {
  var playerSelector = '.gvideo.youtube-video .plyr, .gvideo.vimeo-video .plyr';
  var seekKeys = ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'];
  var observing = false;
  var lastTrigger = null;

  function bindPlayer(container) {
    if (container.dataset.remoteVideoKeys) {
      return;
    }
    container.dataset.remoteVideoKeys = 'true';
    if (!container.hasAttribute('tabindex')) {
      container.setAttribute('tabindex', '0');
    }
    var hideTimer;
    // Plyr listens on this same element, so stopping propagation only keeps GLightbox from navigating.
    container.addEventListener('keydown', function (event) {
      if (seekKeys.indexOf(event.key) !== -1) {
        event.stopPropagation();
      }
      // Plyr only shows controls on mouse/touch, so reveal them for keyboard users.
      container.classList.remove('plyr--hide-controls');
      clearTimeout(hideTimer);
      hideTimer = setTimeout(function () {
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

  function toggleCurrentVideoFromSpace(event) {
    if (event.key !== ' ' || event.defaultPrevented) {
      return;
    }
    // Leave space alone on buttons, links, and form fields so they keep their native behavior.
    if (event.target.closest && event.target.closest('button, a, input, textarea, select, [contenteditable]')) {
      return;
    }
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
        document.addEventListener('keydown', toggleCurrentVideoFromSpace);
        // Capture phase so the trigger is recorded before GLightbox handles the click.
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
        new MutationObserver(function (mutations) {
          mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
              if (node.nodeType === 1) {
                bindAll(node);
              }
            });
            // GLightbox removes its modal on close, which drops focus to the body.
            mutation.removedNodes.forEach(function (node) {
              if (node.nodeType === 1 && node.matches && node.matches('.glightbox-container') && lastTrigger) {
                lastTrigger.focus();
              }
            });
          });
        }).observe(document.body, { childList: true, subtree: true });
      }
      bindAll(context);
    }
  };
})(Drupal);
