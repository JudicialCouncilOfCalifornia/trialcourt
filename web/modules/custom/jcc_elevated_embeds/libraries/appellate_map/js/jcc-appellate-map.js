
'use strict';

/**
 * Details functionality
 */
// Fetch all the "details" element.
const details = document.querySelectorAll(".jcc-appellate-map__legend__detail");

// Add the onclick listeners.
details.forEach((targetDetail) => {
  targetDetail.addEventListener("click", () => {
    // Close all the details that are not targetDetail.
    details.forEach((detail) => {
      if (detail !== targetDetail) {
        detail.removeAttribute("open");
      }
    });
  });
});

/**
 * Details + SVG Viewport connection functionality
 */
const svgGroups = document.querySelectorAll(".jcc-appellate-map__map-viewport svg > g > g > g");

// Add the onclick listeners.
svgGroups.forEach((targetSvgGroup) => {
  targetSvgGroup.addEventListener("click", () => {

    const groupId = targetSvgGroup.getAttribute('id');
    const legendDetails = document.getElementsByName(groupId);

    // Toggle the detail that is associated with the clicked region.
    legendDetails.forEach(legendDetail => {
      legendDetail.open = legendDetail.open !== true;

      // Close the other details that may be open.
      details.forEach((detail) => {
        if (detail.getAttribute('name') !== legendDetail.getAttribute('name')) {
          detail.removeAttribute("open");
        }
      });
    });
  });
});

