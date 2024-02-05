/**
* Ext link icon customization.
**/

(function ($, Drupal) {
    Drupal.behaviors.extlinkcustom = {
      attach: function attach(context, drupalSettings) {
          $(document).ready(function () {
           var exticon = $(document).find('svg.ext');
           if(exticon.length > 0) {
             var parent = exticon.parent('a.button');
             if(parent.length > 0) {
               parent.addClass('hide-ext');
             }
            }
          });   
      }      
    }
})(jQuery, Drupal);
