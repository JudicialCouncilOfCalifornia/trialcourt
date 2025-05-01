/**
 * @file
 * Form adaptations js file.
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.jccFixAutocomplete = {
    attach: function (context, settings) {
      var isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
      if (isChrome) {
        console.log("alex yes it is chrome");

        document.addEventListener("DOMContentLoaded", function () {
          const observer = new MutationObserver(() => {
            const addressDropdown = document.querySelector('select[name="address"]');
            if (addressDropdown) {
              addressDropdown.removeAttribute("autocomplete");
              observer.disconnect(); // Stop observing once found
              console.log("alex addressDropdown treated");
            } else{
              console.log("alex addressDropdown not treated");
            }

            console.log("alex moving to input");

            var addressInput = document.querySelector('input[name="address"]');
            if (addressInput && addressInput.getAttribute("autocomplete") === "off") {
              addressInput.removeAttribute("autocomplete");
              console.log("alex yes autocomplete removed 2");
            } else {
              console.log("alex no autocomplete not removed 2");
            }
          });

          observer.observe(document.body, { childList: true, subtree: true });
        });
      }
    }
  };
})(jQuery, Drupal);
