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
      var docketSearch = '<div id="docket_search_bynumber" style="position:absolute; display:block; width:100%; background-color:#fff; top:2.5rem">' +
        '<form class="sr-only" name="searchFormNumber" id="searchFormNumber" method="post" action="https://appellatecases.courtinfo.ca.gov/search/searchResults.cfm?dist=0&search=number">' +
        '<input type="hidden" name="query_caseNumber" id="query_caseNumber" />' +
        '<input type="hidden" name="bot_check_1" id="bot_check_1" value="Y" checked> <span id="bot_text_1">I am a robot</span>' +
        '<input type="hidden" name="bot_check_6" id="bot_check_6" value="Y">' +
        //'<input class="btn btn-primary" type="submit" name="submit" id="caseNumberSubmit" value="Search by Case Number"/></p>' +
        '</form>' +
        '<a id="lookupDocket" href="#" style="color: #000;">Look up docket at ACCMS from this case number?</a>'
      '</div>';

      $(document).ready(function() {
        if ($('#docket_search_bynumber').length == 0) {
          $('.usa-search').append(docketSearch);
        }

        $('.usa-search input').on('keyup', function (e) {
          var number = $('input[type="search"]').val();
          $('#query_caseNumber').val(number);
        });

        $(document).on('click', '#lookupDocket', function() {
          $('form[name="searchFormNumber"]').submit();
        });
      });
    }
  }
} (jQuery, Drupal));
