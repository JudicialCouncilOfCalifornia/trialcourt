// Presets at: https://docs.cypress.io/api/commands/viewport.html#Arguments
const sizes = [
  ["iphone-6", "landscape"],
  "iphone-6",
  "ipad-2",
  ["ipad-2", "landscape"],
  [1920, 1080],
];

const pages = ["/", "/online-services"];

function cleanName(title) {
  return title.replace(/[.\/#!$%\^&\*;:{}=_`~(),]/g, "-");
}

describe("Inyo VRT", () => {
  sizes.forEach((size) => {
    pages.forEach((page) => {
      const name = cleanName(page + "-" + size).toLowerCase();

      it(`${page} -- ${size}`, () => {
        cy.setResolution(size);
        cy.visit(page);
        cy.compareSnapshot(name, {
          capture: "fullPage",
          errorThreshold: 0, // 1%
          failureThresholdType: "percent",
          dumpInlineDiffToConsole: true,
        });
      });
    });
  });
});
