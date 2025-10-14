(function (Drupal, once) {
  Drupal.behaviors.addressDropdown = {
    attach: function (context, settings) {
      once('addressDropdown', '#address-selector', context).forEach(function (selector) {
        const backToTopBtn = document.getElementById('back-to-top');
        selector.addEventListener("change", function () {
          const targetId = this.value;
          if (targetId) {
            const el = document.getElementById(targetId);
            if (el) {
              el.scrollIntoView({ behavior: "smooth" });
              history.replaceState(null, null, "#" + targetId);
            }
            if (backToTopBtn) {
              backToTopBtn.style.display = "block";
            }
          } else {
            if (backToTopBtn) {
              backToTopBtn.style.display = "none";
            }
          }
        });

        if (backToTopBtn) {
          backToTopBtn.addEventListener("click", function () {
            window.scrollTo({ top: 0, behavior: "smooth" });
            backToTopBtn.style.display = "none";
          });
        }
      });
    }
  };
})(Drupal, once);
