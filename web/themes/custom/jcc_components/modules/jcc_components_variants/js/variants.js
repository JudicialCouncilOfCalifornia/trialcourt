/**
* Variant enhancements.
**/

(function (Drupal) {
  Drupal.behaviors.variant = {
    attach: function attach(context, settings) {

      /**
       * Check a string for JSON structure.
       *
       * @param string str
       *   The string to check for JSON structure.
       *
       * @returns boolean
       *   True if has JSON structure, else false.
       */
      function hasJsonStructure(str) {
        if (typeof str !== 'string') return false;

        try {
          const result = JSON.parse(str);
          const type = Object.prototype.toString.call(result);

          return type === '[object Object]' || type === '[object Array]';
        } catch (err) {
          return false;
        }
      }

      /**
       * Set the variant field value for the clicked option.
       *
       * @param object event
       *   The click event from the variant button.
       */
      function setVariant(event) {
        event.preventDefault();

        const wrapper = this.closest('.form-wrapper');
        const field = wrapper.querySelector('input.form-text');
        // Check for selected variant class and reassign to clicked button.
        const selected = wrapper.querySelector('.variant.selected');

        if (selected) {
          selected.classList.remove('selected');
        }

        this.classList.add('selected');

        // Get the current variant field value and update with clicked value.
        // There could potentially be multiple variant types, so selected values
        // are stored as a stringified object.
        let fieldValue = {}

        if (hasJsonStructure(field.value)) {
          fieldValue = JSON.parse(field.value);
        }
        // Variant values are a key:value pair indicating component:variant.
        const variant = this.getAttribute('data-variant').split(':');
        // Add the variant value to the object, keyed by component.
        fieldValue[variant[0]] = variant[1];
        // Set the new field value as stringified object.
        field.value = JSON.stringify(fieldValue);
      }

      // Prepare all variant buttons.
      const variants = document.querySelectorAll('.variant', context);

      for (const variant of variants) {
        // Check if this variant button already has an event listener.
        const listener = variant.classList.contains('js-varient');
        // Go up to the parent wrapper and down to the related input to get the
        // current field value.
        const selected = variant.closest('.form-wrapper').querySelector('input.form-text').value;
        // Add a selected class to variant if its value is already in the field
        // so the variant button can be styled as selected on load.
        if (hasJsonStructure(selected)) {
          const selectedValue = JSON.parse(selected);
          const variantValue = variant.getAttribute('data-variant').split(':');

          if (selectedValue[variantValue[0]] === variantValue[1]) {
            variant.classList.add('selected');
          }
        }
        // Add the click event listener if it doesn't have one already.
        if (!listener) {
          variant.classList.add('js-varient');
          variant.addEventListener('click', setVariant);
        }
      }

      // Hide text fields with JS so they're still visible if JS is disabled.
      const variantFields = document.querySelectorAll('.field--name-field-variant .form-item, .field--name-field-sub-variant .form-item');

      for (const item of variantFields) {
        item.classList.add('hidden');
      }
    }
  }
})(Drupal);

