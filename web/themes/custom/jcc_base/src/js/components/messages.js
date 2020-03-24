(function() {
  'use strict';

  Drupal.theme.message = ({ text }, { type, id }) => {
    const types = {
      error: {
        class: 'error',
        label: Drupal.t('Error message'),
        role: 'alert'
      },
      info: {
        class: 'info',
        label: Drupal.t('Informational message'),
        role: 'status'
      },
      status: {
        class: 'success',
        label: Drupal.t('Success message'),
        role: 'status'
      },
      warning: {
        class: 'warning',
        label: Drupal.t('Warning message'),
        role: 'alert'
      }
    };

    const messageWrapper = document.createElement('div');

    messageWrapper.setAttribute(
      'class',
      `usa-alert usa-alert--${types[type].class}`
    );
    messageWrapper.setAttribute('role', types[type].role);
    messageWrapper.setAttribute('aria-label', types[type].label);
    messageWrapper.setAttribute('data-drupal-message-id', id);
    messageWrapper.setAttribute('data-drupal-message-type', type);

    messageWrapper.innerHTML = `
      <div class="usa-alert__body">
        <p class="usa-alert__text">
          ${text}
        </p>
      </div>`;

    return messageWrapper;
  };
})(jQuery, Drupal);
