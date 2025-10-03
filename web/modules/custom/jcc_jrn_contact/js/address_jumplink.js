(function (Drupal, once) {
  Drupal.behaviors.addressDropdown = {
    attach: function (context, settings) {
      once('addressDropdown', '#address-selector', context).forEach(function (selector) {
        selector.addEventListener("change", function () {
          const targetId = this.value;
          if (targetId) {
            const el = document.getElementById(targetId);
            if (el) {
              el.scrollIntoView({ behavior: "smooth" });
              history.replaceState(null, null, "#" + targetId);
            }
          }
        });
      });
    }
  };
})(Drupal, once);

(function () {
  const addressDropdown = document.getElementById('address');
  const backToTopBtn = document.getElementById('backToTop');

  if (addressDropdown && backToTopBtn) {
    addressDropdown.addEventListener('change', function() {
      backToTopBtn.style.display = this.value ? 'block' : 'none';
    });
  }
})();
