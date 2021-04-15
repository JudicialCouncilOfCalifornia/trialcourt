(function (Drupal) {
  Drupal.behaviors.courtyardIconSelect = {
    attach: function attach(context, settings) {
      const params = settings.media_boxcast;

      for (const id in params) {
        boxcast.noConflict()('#boxcast-widget-'+id).loadChannel(id, params[id]);
      }
    }
  }
})(Drupal);
