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

        // Get the current icon field value and update with clicked value.
        field.value = this.getAttribute('data-icon-name');

        if (field.value.startsWith('icon-line-white')){
          var new_icon_name = 'icon-line-dark' + field.value.replace('icon-line-white','');
          document.querySelectorAll("[data-icon-name='" + new_icon_name + "']")[0].classList.add('selected');
        } else {
          this.classList.add('selected');
        }
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
          if (selectedValue.startsWith('icon-line-white')){
            var new_icon_name = 'icon-line-dark' + selectedValue.replace('icon-line-white','');
            // var dark_button = button.closest('.field--type-courtyard-icons').querySelector('[data-icon-name="' + new_icon_name + '"]');
            var dark_button = document.querySelector('[data-icon-name="icon-dark-line-circle-information"]');
            dark_button.classList.add('selected');
          } else {
            button.classList.add('selected');
          }
        }

        //Disabling the white-label selection visually
        document.querySelector('.field--type-courtyard-icons .form-element--type-select [label="line-white"]').setAttribute('hidden', 'hidden');;

        // Add the click event listener if it doesn't have one already.
        if (!listener) {
          button.classList.add('js-jcc-courtyard-icons__button');
          button.addEventListener('click', setIcon);
        }
      }

    }
  }
})(Drupal);

