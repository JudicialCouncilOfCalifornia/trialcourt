/**
* Nav tabs enhancement from seven theme.
**/

(function (Drupal) {
    Drupal.behaviors.shuffle = {
      attach: function attach(context, settings) {
        var ul = document.querySelector('.shuffle ul');
        for (var i = ul.children.length; i >= 0; i--) {
          ul.appendChild(ul.children[Math.random() * i | 0]);
        }
      }
    };
  })(Drupal);
