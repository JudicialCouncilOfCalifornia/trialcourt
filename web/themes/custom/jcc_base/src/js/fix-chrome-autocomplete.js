(function() {
  var isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
  if (isChrome) {
    document.addEventListener("DOMContentLoaded", function() {
      // Adjust selector to match your dropdown field
      var addressDropdown = document.querySelector('select[name="address"]');
      if (addressDropdown) {
        addressDropdown.removeAttribute("autocomplete");
      }

      // If it's not a <select> but an <input> styled like a dropdown
      var addressInput = document.querySelector('input[name="address"]');
      if (addressInput && addressInput.getAttribute("autocomplete") === "off") {
        addressInput.removeAttribute("autocomplete");
      }
    });
  }
})();
