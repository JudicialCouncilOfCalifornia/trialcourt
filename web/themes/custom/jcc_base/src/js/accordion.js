/**
 * @file
 * Adds the USWDS accordion button to headings that do not have one.
 *
 * CKEditor 5 cannot edit text inside a <button>, so the Accordion template
 * only has <h2 class="usa-accordion__heading">Title</h2> followed by its
 * .usa-accordion__content. The USWDS accordion needs the button, so it is
 * built here. Headings that already have a button are left alone.
 */
(function (Drupal, once) {
  'use strict';

  var ACCORDION = '.usa-accordion, .usa-accordion--bordered';
  var counter = 0;

  function uniqueId() {
    var id;
    do {
      counter++;
      id = 'jcc-accordion-item-' + counter;
    } while (document.getElementById(id));
    return id;
  }

  Drupal.behaviors.jccBaseAccordion = {
    attach: function (context) {
      once('jcc-base-accordion', '.usa-accordion__heading', context).forEach(function (heading) {
        var content = heading.nextElementSibling;
        if (heading.querySelector('.usa-accordion__button') || !heading.closest(ACCORDION) || !content || !content.classList.contains('usa-accordion__content')) {
          return;
        }
        if (!content.id) {
          content.id = uniqueId();
        }
        // Like the original template, only the first item starts open.
        var expanded = heading.closest(ACCORDION).querySelector('.usa-accordion__heading') === heading;
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'usa-accordion__button';
        button.setAttribute('aria-controls', content.id);
        button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        while (heading.firstChild) {
          button.appendChild(heading.firstChild);
        }
        heading.appendChild(button);
        if (expanded) {
          content.removeAttribute('hidden');
        }
        else {
          content.setAttribute('hidden', '');
        }
      });
    }
  };
})(Drupal, once);
