// Presets at: https://docs.cypress.io/api/commands/viewport.html#Arguments
const sizes = [
  ['iphone-6', 'landscape'],
  'iphone-6',
  'ipad-2',
  ['ipad-2', 'landscape'],
  [1920, 1080],
];

const pages = [
  '/',
];

describe('Newsroom VRT', () => {
  sizes.forEach((size) => {
    pages.forEach((page) => {
      it(`${page} -- ${size}`, () => {
        cy.viewport(size);
        cy.setResolution(size);
        cy.visit(page);
        cy.matchImageSnapshot({
          capture: 'fullPage',
          failureThreshold: 0.01, // 1%
          failureThresholdType: 'percent',
          dumpInlineDiffToConsole: true,
        });
      });
    });
  })
});
