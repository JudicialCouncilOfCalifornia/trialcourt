/**
* Variant enhancements.
**/

(function (Drupal) {
  Drupal.behaviors.variant = {
    attach: function attach(context, settings) {

      function setVariant(event) {
        event.preventDefault();
        const details = this.closest('details');
        const field = details.querySelector('input.form-text');
        field.value = this.getAttribute('data-variant');
      }

      const variants = document.querySelectorAll('.variant', context);

      for (const variant of variants) {
        const listener = variant.classList.contains('js-varient');
        if (!listener) {
          variant.classList.add('js-varient');
          variant.addEventListener('click', setVariant);
        }
      }
    }
  }
})(Drupal);

