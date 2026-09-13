/**
 * @file
 * Table adaptations js file.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.jccTableAdapt = {
    attach: function (context, settings) {
      const excluded_tables = [
        'views-exposed-form-imported-events-events',
        'views-exposed-form-case-block-1'
      ];

      $(once('jcc-table-adapt', '.jcc-section table, .jcc-form + table', context)).each( function() {
        let $currentTable = $(this);
        let $formViewId = $currentTable.siblings('.jcc-form').attr('id');

        // The case view can contain fields that render as an empty string for
        // every result. Views still emits their header and cells because this
        // view uses a custom table template, so remove those columns after
        // the final markup has been built.
        if ($currentTable.closest('.jcc-case-table-view').length) {
          let $headers = $currentTable.find('thead th');
          const emptyColumns = [];
          $headers.each(function (index) {
            const hasValue = $currentTable.find('tbody tr').toArray().some(function (row) {
              return $.trim($(row).children('td').eq(index).text()) !== '';
            });
            if (!hasValue) {
              emptyColumns.push(index);
            }
          });
          emptyColumns.reverse().forEach(function (index) {
            $headers.eq(index).remove();
            $currentTable.find('tbody tr').each(function () {
              $(this).children('td').eq(index).remove();
            });
          });
        }

        $currentTable.addClass('usa-table').removeClass('sortable');
        let $headers = $currentTable.find('thead th');

        $headers.each( function() {
          let $tableHeader = $(this);
          let $headerMarkup = $tableHeader.text();

         if (!excluded_tables.includes($formViewId)) {
           $tableHeader.attr('data-sortable', '');
           $tableHeader.attr('scope', 'col');
           $tableHeader.html($headerMarkup);
         }
        });
      });
    }
  };
})(jQuery, Drupal);
