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
        // BEGIN Load form onto page if global search field exists.
        const searchForm = '<form class="sr-only" name="searchFormNumber" id="searchFormNumber" method="post" target="_blank" action="https://appellatecases.courtinfo.ca.gov/">' +
          '<input type="text" name="query_caseNumber" id="query_caseNumber" />' +
          '<input type="checkbox" name="bot_check_1" id="bot_check_1" value="Y" checked> <span id="bot_text_1">I am a robot</span>' +
          '<input type="checkbox" name="bot_check_6" id="bot_check_6" value="Y">' +
          '</form>';

        if ($('.usa-search input').length && $('#searchFormNumber').length == 0) {
          $('body').append(searchForm);
        }
        // END.

        // BEGIN Global search field integration.
        $('.usa-search input').on('keyup', function() {
          let typedQuery = $('input[type="search"]').val();
          let detectedCases = detectCases(typedQuery);

          // Display docket search option only if case number entered (e.g. S######).
          if (detectedCases) {
            if ($('#docket-search-field-modal').length == 0) {
              // Docket search contextual modal.
              let docketSearch = '<div id="docket-search-field-modal" class="docket-search-field-modal"></div>';
              $('.usa-search').append(docketSearch);

              // Modal docket search form submission event handler.
              $(document).on('click', '.lookupDocket--modal', function () {
                docketQuery(this);
              });
            }
            // Insert/Update modal content.
            $('#docket-search-field-modal').html(docketSearchTriggers(detectedCases, 'modal'));
          } else {
            $('#docket-search-field-modal').remove();
          }
        });

        // If click or touch outside of modal...
        $(document).click(function(event) {
          if(!$(event.target).closest('#docket-search-field-modal').length &&
            !$(event.target).closest('input[type="search"]').length &&
            $('#docket-search-field-modal').is(':visible')) {
            $('#docket-search-field-modal').remove();
          }
        });
        // END.

        // BEGIN Search results page integration.
        if ($('.search').length > 0 && $('.jcc-docket-search').length == 0) {
          const queryString = window.location.search;
          const urlParams = new URLSearchParams(queryString);
          const submittedQuery = urlParams.get('search');
          const searchString = detectCases(submittedQuery);
          const docketSearchBlock = '<div class="search__message--item jcc-docket-search">' +
            '<p>' +
            'Looking for opinions or case information from previous terms? We recommend starting with the <a href="https://appellatecases.courtinfo.ca.gov/search/searchResults.cfm?dist=0&search=number">Docket Search</a> ' +
            'located on the California Courts website where you can search by case number, case name, or names of the parties associated with the case.' +
            '</p>' +
            '</div>';

          $('.search__form').append(docketSearchBlock);

          if (searchString) {
            const searchResultsIntegration = '<p>' +
                docketSearchTriggers(searchString, 'page') +
              '</p>';
            $('.jcc-docket-search').prepend(searchResultsIntegration);

            // Page results docket search form submission event handler.
            $(document).on('click', '.lookupDocket--page', function () {
              docketQuery(this);
            });
          }
        }
        // END.

        // Detect and extract first case number from query string (e.g. S170280).
        function detectCases(query) {
          query = query.match(/[A-Za-z][0-9]{6}/g);

          return query;
        }

        // Docket search triggers.
        function docketSearchTriggers(caseNumbers, type) {
          let cases = new Array();
          jQuery.each(caseNumbers, function(index, item) {
            item = item.toUpperCase();
            let link = '<a class="lookupDocket--' + type + '" href="javascript:void(0);" data-case="' + item + '" aria-label="Look up case ' + item + ' from the California Courts website in a new browser window.">' + item + '</a>';
            var isLastElement = index == caseNumbers.length -1;
            if (cases.length > 0 && isLastElement) {
              link = 'and ' + link;
            }
            cases.push(link);
          });
          // Delimit format depending on the number of cases.
          if (cases.length <= 2) {
            cases = cases.join(' ');
          } else {
            cases = cases.join(', ');
          }
          const triggerBlock = 'Look up ' + cases + ' from the California Courts website?';

          return triggerBlock;
        }

        // Set case number in form and submit redirected query.
        function docketQuery(trigger) {
          let query = $(trigger).attr('data-case');
          if (query) {
            // Set district for docket search URL.
            const districtLetter = query[0].charAt(0);
            let district;
            switch(districtLetter) {
              case 'S':
                district = 0;
                break;
              case 'A':
                district = 1;
                break;
              case 'B':
                district = 2;
                break;
              case 'C':
                district = 3;
                break;
              case 'D':
                district = 41;
                break;
              case 'E':
                district = 42;
                break;
              case 'F':
                district = 43;
                break;
              case 'F':
                district = 5;
                break;
              case 'G':
                district = 6;
                break;
              default:
                district = 0;
            }
            $('#searchFormNumber').attr('action', 'https://appellatecases.courtinfo.ca.gov/search/searchResults.cfm?dist=' + district + '&search=number');
          }
          $('#query_caseNumber').val(query);
          $('form[name="searchFormNumber"]').submit();
        }
      });
    }
  }
} (jQuery, Drupal));
