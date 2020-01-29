(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.jumpNav = {
    attach: function (context) {
      const $jumpItems = $('[id^="jump-"]', context);
      const $jumpNavContainer = $('.jcc-jump-nav', context);
      const $jumpList = $('.jcc-jump-nav ul', context);

      // Hide the Jump Nav, which may have a title in it, and may also be empty.
      $jumpNavContainer.hide();

      if ($jumpItems && $jumpItems.length > 0) {
        // Add the proper column variant class to the hero.
        $jumpList.parents('.jcc-hero').addClass('jcc-hero--has-two-columns-threequarter');

        $jumpItems.once('jump-nav').each(function(index, item) {
          // Find the title, which could be an h2 or h3, and is formatted text.
          let itemTitle = $(item).find('.jcc-header-group__title').html();

          // Create a list item and append the item to the jump nav.
          if (itemTitle) {
            $jumpList.append(`
              <li class="usa-sidenav__item">
                <a href="#${ $(item).attr('id') }">${ itemTitle }</a>
              </li>
            `);
          }
        });

        // Show the Jump Nav component.
        $jumpNavContainer.show();
      }
    }
  };

})(jQuery, Drupal);
