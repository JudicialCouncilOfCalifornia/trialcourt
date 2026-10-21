/**
 * @file
 * Turns read-more triggers of the Steps template into buttons.
 *
 * CKEditor 5 cannot edit text inside a <button>, so the Steps template only
 * has <p class="jcc-read-more__trigger">Label</p> followed by its
 * .jcc-read-more__content. The button, its icon and the a11y-toggle link to
 * the content are built here. Triggers that already are buttons are left alone.
 *
 * On page load this runs before a11y-toggle (Courtyard) registers the toggles
 * on DOMContentLoaded; content attached later is registered here.
 */
(function (Drupal, once) {
  'use strict';

  var counter = 0;

  function uniqueId() {
    var id;
    do {
      counter++;
      id = 'jcc-read-more-' + counter;
    } while (document.getElementById(id));
    return id;
  }

  Drupal.behaviors.jccBaseReadMore = {
    attach: function (context) {
      var built = 0;
      once('jcc-base-read-more', 'p.jcc-read-more__trigger', context).forEach(function (trigger) {
        var content = trigger.nextElementSibling;
        if (!content || !content.classList.contains('jcc-read-more__content')) {
          return;
        }
        if (!content.id) {
          content.id = uniqueId();
        }
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'jcc-read-more__trigger usa-button usa-button--unstyled';
        button.setAttribute('data-a11y-toggle', content.id);
        while (trigger.firstChild) {
          button.appendChild(trigger.firstChild);
        }
        button.insertAdjacentHTML('beforeend', ' <svg class="icon icon-expand_more" role="img" title="Expand"><use href="#i-expand_more"></use></svg>');
        trigger.parentNode.replaceChild(button, trigger);
        built++;
      });
      // On page load a11y-toggle registers every toggle itself after this.
      if (built && context !== document && typeof window.a11yToggle === 'function') {
        window.a11yToggle(context);
      }
    }
  };
})(Drupal, once);
