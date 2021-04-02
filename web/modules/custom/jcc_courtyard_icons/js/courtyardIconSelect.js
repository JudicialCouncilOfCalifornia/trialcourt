/**
* Courtyard Icon Select.
**/

(function (Drupal) {
  Drupal.behaviors.courtyardIconSelect = {
    attach: function attach(context, settings) {

      /**
       * Set the button field value for the clicked option.
       *
       * @param object event
       *   The click event from the button.
       */
      function setIcon(event) {
        event.preventDefault();

        const wrapper = this.closest('.form-wrapper');
        const field = wrapper.querySelector('select');

        // Check for selected button class and reassign to clicked button.
        const selected = wrapper.querySelector('.jcc-courtyard-icons__button.selected');

        if (selected) {
          selected.classList.remove('selected');
        }

        this.classList.add('selected');

        // Get the current icon field value and update with clicked value.
        field.value = this.getAttribute('data-icon-name');
      }

      // Prepare all icon buttons.
      const buttons = document.querySelectorAll('.jcc-courtyard-icons__button', context);

      for (const button of buttons) {
        // Check if this button already has an event listener.
        const listener = button.classList.contains('js-jcc-courtyard-icons__button');
        // Go up to the parent wrapper and down to the related input to get the
        // current field value.
        const select = button.closest('.form-wrapper').querySelector('select');
        // Add a selected class to button if its value is already in the field
        // so the button button can be styled as selected on load.
        const selectedValue = select.value;
        const buttonValue = button.getAttribute('data-icon-name');

        if (selectedValue === buttonValue) {
          button.classList.add('selected');
        }

        // Add the click event listener if it doesn't have one already.
        if (!listener) {
          button.classList.add('js-jcc-courtyard-icons__button');
          button.addEventListener('click', setIcon);
        }
      }

    }
  }
})(Drupal);

