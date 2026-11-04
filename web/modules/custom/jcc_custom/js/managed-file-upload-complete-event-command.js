(function ($, Drupal) {

  "use strict";

  /**
   * Set the form name field from the file name if name is empty.
   */
  Drupal.AjaxCommands.prototype.triggerManagedFileUploadComplete = function (context) {
    const form = context.$form[0];
    const fileLink = form.querySelector('.js-form-managed-file .file a');
    const nameField = form.querySelector('.js-form-item-name-0-value input');

    // Bulk import and failed uploads may not provide these elements.
    if (!nameField || !fileLink) {
      return;
    }

    if (nameField.value == '') {
      nameField.value = fileLink.innerHTML.replace(/\.[^/.]+$/, "");
    }
  };

}(jQuery, Drupal));
