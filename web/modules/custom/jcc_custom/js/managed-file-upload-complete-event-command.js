(function ($, Drupal) {

  "use strict";

  /**
   * Set the form name field from the file name if name is empty.
   */
  Drupal.AjaxCommands.prototype.triggerManagedFileUploadComplete = function (context) {
    const form = context.$form[0];
    const fileName = form.querySelector('.js-form-managed-file .file a').innerHTML.replace(/\.[^/.]+$/, "");
    const nameField = form.querySelector('.js-form-item-name-0-value input');

    if (nameField.value == '') {
      nameField.value = fileName;
    }
  };

}(jQuery, Drupal));
