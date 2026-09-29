/**
 * @file
 * Retag FullCalendar's month title from h2 to h3 on the newsroom calendar.
 *
 * FullCalendar v4 always renders .fc-center > h2. That sits under the
 * Calendar section title, so the month must be h3 for a valid outline.
 *
 * Also retag the nested list header after Views AJAX. That refresh re-runs
 * events:page_1 without parent_views, so PHP cannot see it as embedded.
 */
(function (Drupal) {
  'use strict';

  /**
   * Replace FullCalendar toolbar month h2 elements with h3.
   *
   * @param {Element} root
   *   Calendar element or document.
   */
  function retagMonthTitle(root) {
    if (!root || !root.querySelectorAll) {
      return;
    }
    var titles = root.matches && root.matches('.js-drupal-fullcalendar')
      ? root.querySelectorAll('.fc-center > h2')
      : root.querySelectorAll('.view-display-id-page_2 .js-drupal-fullcalendar .fc-center > h2');
    for (var i = 0; i < titles.length; i++) {
      var h2 = titles[i];
      var h3 = document.createElement('h3');
      h3.innerHTML = h2.innerHTML;
      for (var j = 0; j < h2.attributes.length; j++) {
        h3.setAttribute(h2.attributes[j].name, h2.attributes[j].value);
      }
      h2.parentNode.replaceChild(h3, h2);
    }
  }

  /**
   * Promote the nested Hearings header after AJAX replaces page_1.
   */
  function retagEmbeddedHearings() {
    var titles = document.querySelectorAll('.view-display-id-page_2 .view-display-id-page_1 .view__header h3.jcc-card__title');
    for (var i = 0; i < titles.length; i++) {
      var h3 = titles[i];
      var h2 = document.createElement('h2');
      h2.innerHTML = h3.innerHTML;
      for (var j = 0; j < h3.attributes.length; j++) {
        h2.setAttribute(h3.attributes[j].name, h3.attributes[j].value);
      }
      h3.parentNode.replaceChild(h2, h3);
    }
  }

  Drupal.behaviors.jccNewsroomCalendarHeadings = {
    attach: function (context) {
      retagMonthTitle(context === document ? document : context);
      retagEmbeddedHearings();

      var calendars = document.querySelectorAll('.view-display-id-page_2 .js-drupal-fullcalendar');
      for (var i = 0; i < calendars.length; i++) {
        var calendar = calendars[i];
        if (calendar.getAttribute('data-jcc-calendar-headings')) {
          continue;
        }
        calendar.setAttribute('data-jcc-calendar-headings', '1');
        (function (observed) {
          var observer = new MutationObserver(function () {
            retagMonthTitle(observed);
          });
          observer.observe(observed, { childList: true, subtree: true });
        })(calendar);
      }
    }
  };
})(Drupal);
