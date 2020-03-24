import flatpickr from "flatpickr";

(function($) {
  "use strict";

  Drupal.behaviors.datefinder = {
    attach: function(context) {
      const inputEl = $('[data-drupal-selector="edit-input-date"]');
      const resultContainerSelector = ".jcc-datefinder__adjacent-dates";
      const resultTextSelector = ".jcc-datefinder__date";
      const daysToAddSelector = '[data-drupal-selector="edit-days-to-add"]';
      const prettyFormat = "F j, Y";

      // Use flatpickr js to create datepicker.
      flatpickr(inputEl, {
        altInput: true,
        altFormat: prettyFormat,
        dateFormat: "Y-m-d"
      });

      // Calculate and display result date.
      inputEl.on("change keyup", function() {
        const id = $(this)
          .closest(".datefinder")
          .attr("id");
        const idStr = "#" + id + " ";
        const daysToAdd = $(idStr + daysToAddSelector).val();
        // 60 sec * 60 min * 24 hours = 86400
        const resultDateMilisecs = new Date(
          Date.parse($(this).val()) + daysToAdd * 86400 * 1000
        );

        if (resultDateMilisecs != "Invalid Date") {
          // Show result when date is valid.
          $(idStr + resultContainerSelector).removeAttr("hidden");
          const resultDateString = flatpickr.formatDate(
            resultDateMilisecs,
            prettyFormat
          );
          $(idStr + resultTextSelector).html(resultDateString);
        } else {
          // Hide result when date is invalid.
          $(idStr + resultContainerSelector).attr("hidden", true);
          $(idStr + resultTextSelector).html("");
        }
      });
    }
  };
})(jQuery, Drupal);
