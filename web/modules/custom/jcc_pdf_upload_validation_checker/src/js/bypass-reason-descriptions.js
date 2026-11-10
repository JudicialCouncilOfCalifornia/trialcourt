(function (Drupal, drupalSettings, once) {
  Drupal.behaviors.jccBypassReasonDescriptions = {
    attach(context) {
      const descriptions = drupalSettings.jccPdfUploadValidationChecker?.bypassReasonDescriptions || {};

      const updateDescription = (select) => {
        const targetId = select.getAttribute('data-description-target');
        if (!targetId) {
          return;
        }

        const target = document.getElementById(targetId);
        if (!target) {
          return;
        }

        const selectedValue = select.value;
        const description = descriptions[selectedValue] || '';
        target.innerHTML = description;
      };

      once('jcc-bypass-reason-description', '.js-jcc-pdf-bypass-reason-select', context).forEach((select) => {
        updateDescription(select);
        select.addEventListener('change', () => updateDescription(select));
      });
    },
  };
})(Drupal, drupalSettings, once);
