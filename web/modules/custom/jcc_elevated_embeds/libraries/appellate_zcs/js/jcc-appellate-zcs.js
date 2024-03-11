
'use strict';

/**
 * @file
 * Functionality for the Zip/City/County District search.
 */

(function ($, Drupal) {

  Drupal.behaviors.jccAppellateZCSearch = {
    attach: function (context, settings) {

      const search = document.getElementById('zcs-input');
      const matchList = document.getElementById('zcs-matches');

      /**
       * Output the results in a list.
       */
      const outputMatchesHtml = (matches, searchText) => {
        let html = '';

        if (matches.length > 0) {

          // For each of our results, create an item with the zip city, and link info.
          html = matches.map(function(match) {
            if (match[0] !== "0" && match[1].search !== null && match[1].search.length > 0) {
              return `
              <li class="zcs__item">
                <a href="${match[1].url}"
                   title="Go to ${match[1].district}"
                   aria-label="Select ${match[1].city}, California, Zip Code ${match[1].zip}, ${match[1].county} County">
                  <span class="zcs__city">${match[1].city}, CA</span>
                  <span class="zcs__zip">${match[1].zip}</span>
                  <span class="zcs__county">${match[1].county} Co.</span>
                  <span class="zcs__district-name">${match[1].district}</span>
                </a>
              </li>
              `;
            }
            // If no matches, show the no results text.
            else {
              return `<li class="zcs__item"><span class="zcs__no-match">${match[1]}</span></li>`;
            }
          }).join('');

          // Add wrapping around our auto output.
          html = '<ul class="zcs__items">' + html + '</ul>';
        }

        if (searchText.length <= 2 ) {
          matchList.innerHTML = '';
        }

        matchList.innerHTML = html;
      };

      /**
       * Submit the fetch with the search query from the input.
       */
      const searchZCSinfo = async searchText => {
        let matches = [];

        if (searchText.length >= 3 ) {
          const res = await fetch('/jcc-elevated/embeds/autocomplete/zcs?q=' + searchText);
          const data = await res.json();
          matches = Object.entries(data);
        }

        if (searchText.length <= 2 ) {
          matches = [];
        }

        outputMatchesHtml(matches, searchText);
      };

      search.addEventListener('input', () => searchZCSinfo(search.value));
    }
  };
})(jQuery, Drupal);

/**
 * Key up and down through the autocomplete list of items, from the input.
 */
document.addEventListener('keydown', e => {
  if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') {
    return;
  }

  const focusElem = document.querySelector(':focus');
  const tabElements = [...document.querySelectorAll('#zcs-input, #zcs-matches .zcs__item > a')];
  const tabElementsCount = tabElements.length - 1;

  if (!tabElements.includes(focusElem)) {
    return;
  }
  e.preventDefault();

  const focusIndex = tabElements.indexOf(focusElem);
  let elemToFocus;

  if (e.key === 'ArrowUp') {
    elemToFocus = tabElements[focusIndex > 0 ? focusIndex - 1 : tabElementsCount];
  }
  if (e.key === 'ArrowDown') {
    elemToFocus = tabElements[focusIndex < tabElementsCount ? focusIndex + 1 : 0];
  }

  elemToFocus.focus();
});

const svgGroups = document.querySelectorAll(".block__jcc-appellate-zcs__map__container svg > g > g > g");
const mapLinkItems = document.querySelectorAll(".zcs-map-link__item");

/**
 * Trigger the corresponding link of the click map district.
 * Trigger hover of link when svg is hovered.
 */
['mouseup', 'mouseover','mouseout'].forEach((evt) => {
  svgGroups.forEach((targetSvgGroup) => {
    targetSvgGroup.addEventListener(evt, (e) => {

      // Using the ID from the svg group, find the link with the same name.
      const groupId = targetSvgGroup.getAttribute('id');
      const LinkDetails = document.getElementsByName(groupId);

      // Find the link with the matching name/ID and map class, and trigger it.
      if (e.type === "mouseup") {
        LinkDetails.forEach(LinkDetail => {
          if (LinkDetail.classList.contains('zcs-map-link__item')) {
            LinkDetail.click();
          }
        });
      }

      if (e.type === "mouseover") {
        mapLinkItems.forEach(targetMapLink => {
          if (targetMapLink.getAttribute('name') === groupId) {
            targetMapLink.classList.add('hovered');
          }
        });
      }

      if (e.type === "mouseout") {
        mapLinkItems.forEach(targetMapLink => {
          if (targetMapLink.getAttribute('name') === groupId) {
            targetMapLink.classList.remove('hovered');
          }
        });
      }
    });
  });
});

/**
 * Trigger hover of svg when link is hovered.
 */
['mouseover','mouseout'].forEach((evt) => {
  mapLinkItems.forEach((targetMapLink) => {
    targetMapLink.addEventListener(evt, (e) => {

      const linkName = targetMapLink.getAttribute('name');
      const svgItem = document.getElementById(linkName);

      if (e.type === "mouseover") {
        svgGroups.forEach((svgGroupItem) => {
          if (svgGroupItem !== svgItem) {
            svgGroupItem.classList.add('not-hovered');
          }
        });
      }

      if (e.type === "mouseout") {
        svgGroups.forEach((svgGroupItem) => {
          if (svgGroupItem !== svgItem) {
            svgGroupItem.classList.remove('not-hovered');
          }
        });
      }
    });
  });
});



