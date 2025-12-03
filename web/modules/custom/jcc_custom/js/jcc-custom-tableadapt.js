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

      const excluded_tables = [
        'views-exposed-form-imported-events-events',
        'views-exposed-form-case-block-1'
      ];

      $('.jcc-section table', context).add($formTable).each( function() {
        let $currentTable = $(this);
        let $headers = $currentTable.find('thead th');

        $currentTable.addClass('usa-table').removeClass('sortable');

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
