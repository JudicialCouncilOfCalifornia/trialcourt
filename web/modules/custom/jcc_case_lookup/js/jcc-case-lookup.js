/**
 * @file
 * JCC Case lookup integrations & behaviors.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Behavior description.
   */
  Drupal.behaviors.jccCaseLookup = {
    attach: function (context) {
      $(document).ready(function() {
        $('.usa-search input').on('keyup', function (e) {
          var query = $('input[type="search"]').val();
          var caseNumber = query.match(/[Ss][0-9]{6}/g);

          // Display docket search option only if case number entered (e.g. S######).
          if (caseNumber) {
            if ($('#docket-search-bynumber').length == 0) {
              // Docket search contextual modal.
              var docketSearch = '<div id="docket-search-bynumber" class="docket-search-bynumber">' +
                '<form class="sr-only" name="searchFormNumber" id="searchFormNumber" method="post" target="_blank" action="https://appellatecases.courtinfo.ca.gov/search/searchResults.cfm?dist=0&search=number">' +
                '<input type="text" name="query_caseNumber" id="query_caseNumber" />' +
                '<input type="checkbox" name="bot_check_1" id="bot_check_1" value="Y" checked> <span id="bot_text_1">I am a robot</span>' +
                '<input type="checkbox" name="bot_check_6" id="bot_check_6" value="Y">' +
                '</form>' +
                'Looking for docket information? <a id="lookupDocket" href="#" aria-label="Look up case ' + caseNumber + ' at the Appellate Courts Case Information site in a new browser window.">Look up case ' + caseNumber + '</a> at the Appellate Courts Case Information site.' +
              '</div>';
              $('.usa-search').append(docketSearch);
            }
            // Set
            $('#query_caseNumber').val(caseNumber);
          } else {
            $('#docket-search-bynumber').remove();
          }
        });

        $(document).on('click', '#lookupDocket', function () {
          $('form[name="searchFormNumber"]').submit();
        });
      });
    }
  }
} (jQuery, Drupal));
