(function ($, Drupal) {
  Drupal.behaviors.hideBodyWrapper = {
    attach: function (context, settings) {
      if (window.location.pathname === '/node/add/news') {
        $('#edit-body-wrapper', context).once('hide-body').hide();
      }
    }
  };
})(jQuery, Drupal);
