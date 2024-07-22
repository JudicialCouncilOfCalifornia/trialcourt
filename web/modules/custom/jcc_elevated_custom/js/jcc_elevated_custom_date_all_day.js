/**
 * @file
 * Contains jcc_elevated_custom_date_all_day.js.
 */

(function ($, Drupal) {
  'use strict';

  // Datetime Range All Day.
  Drupal.behaviors.JccElevatedCustomDateRangeAllDay = {
    attach: function () {

      $('.field--widget-jcc-elevated-custom-daterange-all-day fieldset').each(function () {
        let $this = $(this);

        let allDayCheckbox = $this.find('[name$="[all_day]"]');
        let enableEndDateFieldCheckbox = $this.find('[name$="[enable_end_date]"]');
        let dateStartField = $this.find('[name$="[value][date]"]');
        let dateEndField = $this.find('[name$="[end_value][date]"]');
        let timeStartField = $this.find('[name$="[value][time]"]');
        let timeEndField = $this.find('[name$="[end_value][time]"]');

        let dateStartLabel = dateStartField.parents('.form-datetime-wrapper').find('h4');
        let dateEndLabel = dateEndField.parents('.form-datetime-wrapper').find('h4');

        const timeStart = '00:00:00';
        const timeEnd = '23:59:59';

        // set the labels based on the checkbox status.
        function enableEndDateField() {

          // If "Enable end date" is NOT checked.
          if (!enableEndDateFieldCheckbox.is(':checked')) {
            dateEndField.hide();
            dateEndLabel.hide();

            // If "Enable end date" is checked and all day is checked.
            if (allDayCheckbox.is(':checked')) {
              dateStartLabel.text('Date');
              dateEndLabel.hide();
            }

            // If "Enable end date" is NOT checked and all day is NOT checked.
            if (!allDayCheckbox.is(':checked')) {
              dateStartLabel.text('Date and start time');
              dateEndLabel.show();
              dateEndLabel.text('End time');
            }

            if (dateStartField.val() !== '') {
              dateEndField.val(dateStartField.val());
            }
          }

          // If "Enable end date" is checked.
          if (enableEndDateFieldCheckbox.is(':checked')) {
            dateEndField.show();
            dateEndLabel.show();

            // If "Enable end date" is checked and all day is checked.
            if (allDayCheckbox.is(':checked')) {
              dateStartLabel.text('Start date');
              dateEndLabel.text('End date');
            }

            // If "Enable end date" is checked and all day is NOT checked.
            if (!allDayCheckbox.is(':checked')) {
              dateStartLabel.text('Start date and time');
              dateEndLabel.text('End date and time');
            }
          }
        }

        // Show or hide the time fields depending on the checkbox status.
        function changeAllDay() {
          if (allDayCheckbox.is(':checked')) {
            timeStartField.hide();
            timeEndField.hide();

            if (dateStartField.val() !== '') {
              timeStartField.val(timeStart);
            }

            if (dateEndField.val() !== '') {
              timeEndField.val(timeEnd);
            }
          }

          if (!allDayCheckbox.is(':checked')) {
            timeStartField.show();
            timeEndField.show();
          }

          enableEndDateField();
        }

        changeAllDay();
        enableEndDateField();

        allDayCheckbox.change(changeAllDay);
        enableEndDateFieldCheckbox.change(enableEndDateField);

        // Change the time field values depending on the checkbox status.
        dateStartField.change(function () {
          if (allDayCheckbox.is(':checked')) {
            if (dateStartField.val() !== '') {
              timeStartField.val(timeStart);
            }
            else {
              timeStartField.val('');
            }
          }
        });

        // If the end date is edited.
        dateEndField.change(function () {
          if (allDayCheckbox.is(':checked')) {
            if (dateEndField.val() !== '') {
              timeEndField.val(timeEnd);
            }
            else {
              timeEndField.val('');
            }
          }
        });

      });
    }
  };
})(jQuery, Drupal);

