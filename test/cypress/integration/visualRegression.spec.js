// Set some environment variables.
const env = {
  "failOnSnapshotDiff": false,
}

Cypress.env('failOnSnapshotDiff', false);

const sizes = [
  ['iphone-6', 'landscape'],
  'iphone-6',
  'ipad-2',
  ['ipad-2', 'landscape'],
  [1920, 1080],
];

const pages = [
  '/',
  '/online-services',
];

describe('VRT', () => {
  sizes.forEach((size) => {
    pages.forEach((page) => {
      it(`${page} -- ${size}`, () => {
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
