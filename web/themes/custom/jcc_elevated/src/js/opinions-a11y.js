document.addEventListener('DOMContentLoaded', function () {
  var liveRegion = document.querySelector('[data-opinions-results-live]');
  if (!liveRegion) {
    return;
  }

  var announcement = liveRegion.getAttribute('data-results-announcement');
  if (!announcement) {
    return;
  }

  // Force a text update after load so screen readers announce changes.
  liveRegion.textContent = '';
  window.setTimeout(function () {
    liveRegion.textContent = announcement;
  }, 100);
});
