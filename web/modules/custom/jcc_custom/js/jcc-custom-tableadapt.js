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

      $(once('jcc-table-adapt', '.jcc-section table, .jcc-form + table, .jcc-case-table-view > table.usa-table', context)).each(function () {
        let $currentTable = $(this);
        let $caseView = $currentTable.closest('.jcc-case-table-view');
        let $exposedForm = $currentTable.siblings('.jcc-form');
        if (!$exposedForm.length && $caseView.length) {
          $exposedForm = $caseView.find('.jcc-form').first();
        }
        const formViewId = $exposedForm.attr('id');

        // The case view can contain fields that render as an empty string for
        // every result. Views still emits their header and cells because this
        // view uses a custom table template, so remove those columns after
        // the final markup has been built.
        if ($caseView.length) {
          const $headers = $currentTable.find('thead th');
          const rows = $currentTable.find('tbody tr').toArray();
          const emptyColumns = [];

          // Keep the table headers when there are no results to compare.
          if (rows.length) {
            $headers.each(function (index) {
              const hasValue = rows.some(function (row) {
                const cellText = $(row).children('td').eq(index).text();
                return cellText.replace(/\u00a0/g, ' ').trim() !== '';
              });
              if (!hasValue) {
                emptyColumns.push(index);
              }
            });
            emptyColumns.reverse().forEach(function (index) {
              $headers.eq(index).remove();
              rows.forEach(function (row) {
                $(row).children('td').eq(index).remove();
              });
            });
          }
        }

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
