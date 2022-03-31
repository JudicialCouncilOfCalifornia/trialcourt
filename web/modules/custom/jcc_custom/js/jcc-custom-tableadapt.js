/**
 * @file
 * Table adaptations js file.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.jccTableAdapt = {
    attach: function (context, settings) {
      var $formTable = $('.jcc-form', context).siblings('table');
      var $formViewId = $('.jcc-form', context).attr('id');

      $('.jcc-section table', context).add($formTable).each( function() {
        let $currentTable = $(this);
        let $headers = $currentTable.find('thead th');

        $currentTable.wrap('<div class="usa-table-container--scrollable"></div>');
        $currentTable.addClass('usa-table').removeClass('sortable');

        $headers.each( function() {
          let $tableHeader = $(this);
          let $headerMarkup = $tableHeader.text();

          if ($formViewId != 'views-exposed-form-imported-events-events') {
            $tableHeader.attr('data-sortable', '');
            $tableHeader.attr('scope', 'col');
            $tableHeader.attr('role', 'columheader');
            $tableHeader.html($headerMarkup);
          }
        });
      });
    }
  };
})(jQuery, Drupal);
