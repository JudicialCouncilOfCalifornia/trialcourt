/**
 * @file
 * Form adaptations js file.
 */

(function ($, Drupal) {
  Drupal.behaviors.loadSelectOptionsIfChrome = {
    attach: function (context) {
      const isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
      if (!isChrome) {
        console.log('alexwu Not Chrome. Skipping behavior.');
        return;
      }

      const $select = $('#edit-addresstest-state-province--2', context);
      console.log('alexwu Select element found:', $select.length > 0);

      if ($select.length && $select.find('option').length <= 1) {
        console.log('alexwu Select field is empty or has only one option. Proceeding to populate.');
      } else {
        if ($select.data('chosen')) {
          $select.chosen('destroy');
          console.log('alexwu chosen destroyed.');
        }

        // Reinitialize Chosen
        $select.chosen();
        console.log('alexwu chosen reinitialized.');
      }
    }
  };
})(jQuery, Drupal);
