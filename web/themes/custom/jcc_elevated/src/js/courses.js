/**
 * Drupal field customizations.
 **/

(function ($, Drupal) {
  Drupal.behaviors.extlinkcustom = {
    attach: function attach(context, drupalSettings) {
      function docReady(fn) {
        // See if DOM is already available.
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
          // Call on next available tick.
          setTimeout(fn, 1);
        } else {
          document.addEventListener('DOMContentLoaded', fn);
        }
      }

      docReady(function () {
        const shs = Array.from(document.querySelectorAll('.shs-container'));
        if (shs) {
          const labelMaker  = function(){
            let levels = Array.from(document.querySelectorAll('[data-shs-level]'));
            if (levels) {
              levels.forEach(level => {
                let label = level.querySelector('label');
                if (label) {
                  label.removeAttribute('class');
                  switch (level.getAttribute('data-shs-level')) {
                    case '0':
                      label.textContent = 'Assignment';
                      break;
                    case '1':
                      label.textContent = 'Topic';
                      break;
                    case '2':
                      label.textContent = 'Subtopic';
                      break;
                  }
                }
              });
            }
          };

          const observeSelectEvents = function() {
            let selects = Array.from(document.querySelectorAll('.shs-select'));
            selects.forEach(select => {
              select.addEventListener('change', function() {
                setTimeout(function() {
                  labelMaker();
                  observeSelectEvents();
                }, 500);
              });
            });
          };

          labelMaker();
          observeSelectEvents();
        }
      });
    }
  };
})(jQuery, Drupal);
