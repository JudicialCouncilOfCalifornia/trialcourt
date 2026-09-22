(function ($, once, Drupal) {
  'use strict';

  Drupal.behaviors.megaMenu = {
    attach(context) {
      const mainLinks = $('#slick-menu li > a');

      once('megaMenuToggle', '#slick-menu li > a', context).forEach((element) => {
        $(element).on('click', function (e) {
          if ($(this).hasClass('has-children')) {
            e.preventDefault();

            const megaId = $(this).attr('data-mega-menu-id');
            const subMenuTarget = $('#mega-menu #' + megaId + ' ul');

            mainLinks.removeClass('mega-menu-open');

            if (subMenuTarget.hasClass('display-none')) {
              $('#mega-menu ul').addClass('display-none');
              subMenuTarget.removeClass('display-none');
              $(this).addClass('mega-menu-open');
            }
            else {
              $('#mega-menu ul').addClass('display-none');
              $(this).removeClass('mega-menu-open');
            }
          }
        });
      });
    }
  };
})(jQuery, once, Drupal);
