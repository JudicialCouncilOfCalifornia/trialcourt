/**
 * Nav tabs enhancement from seven theme.
 */

(function ($, once, Drupal) {
  function init(tab) {
    const $tab = $(tab);
    const $target = $tab.find('[data-drupal-nav-tabs-target]');
    const isCollapsible = $tab.hasClass('is-collapsible');
    function openMenu(e) {
      $target.toggleClass('is-open');
    }
    function handleResize(e) {
      $tab.addClass('is-horizontal');
      const $tabs = $tab.find('.tabs');
      const isHorizontal = $tabs.outerHeight() <= $tabs.find('.tabs__tab').outerHeight();
      $tab.toggleClass('is-horizontal', isHorizontal);
      if (isCollapsible) {
        $tab.toggleClass('is-collapse-enabled', !isHorizontal);
      }
      if (isHorizontal) {
        $target.removeClass('is-open');
      }
    }
    $tab.addClass('position-container is-horizontal-enabled');
    $tab.on('click.tabs', '[data-drupal-nav-tabs-trigger]', openMenu);
    $(window).on('resize.tabs', Drupal.debounce(handleResize, 150)).trigger('resize.tabs');
  }
  Drupal.behaviors.navTabs = {
    attach: function attach(context, settings) {
      const notSmartPhone = window.matchMedia('(min-width: 300px)');
      if (notSmartPhone.matches) {
        once('nav-tabs', '[data-drupal-nav-tabs]', context).forEach(init);
      }
    }
  };
})(jQuery, once, Drupal);
