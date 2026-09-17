/**
 * @file
 * Table adaptations js file.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.jccTableAdapt = {
    attach: function (context, settings) {
      const excludedTables = [
        'views-exposed-form-imported-events-events',
        'views-exposed-form-case-block-1'
      ];

      $(once('jcc-table-adapt', '.jcc-section table, .jcc-form ~ table, .jcc-case-table-view > table.usa-table', context)).each(function () {
        let $currentTable = $(this);
        let $caseView = $currentTable.closest('.jcc-case-table-view');
        let $exposedForm = $currentTable.siblings('.jcc-form');
        if (!$exposedForm.length && $caseView.length) {
          $exposedForm = $caseView.find('.jcc-form').first();
        }
        const formViewId = $exposedForm.attr('id');

        $currentTable.addClass('usa-table').removeClass('sortable');
        let $headers = $currentTable.find('thead th');

        $headers.each( function() {
          let $tableHeader = $(this);
          let $headerMarkup = $tableHeader.text();

         if (!excludedTables.includes(formViewId)) {
           $tableHeader.attr('data-sortable', '');
           $tableHeader.attr('scope', 'col');
           $tableHeader.html($headerMarkup);
         }
        });
      });
    }
  };
})(jQuery, Drupal);
