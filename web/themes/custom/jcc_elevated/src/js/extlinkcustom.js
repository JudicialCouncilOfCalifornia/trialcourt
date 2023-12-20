/**
* Nav tabs enhancement from seven theme.
**/

(function ($, Drupal) {
    Drupal.behaviors.extlinkcustom = {
      attach: function attach(context, settings) {       
        var exticon = $(context).find('svg.ext');   
        if(exticon.length > 0){
          var parent = exticon.parent('a.button');
          if(parent.length > 0) {           
            parent.addClass('hide-ext');
          }
        }    

      }
    };
  })(jQuery, Drupal);
