const { defineConfig } = require("cypress");
const getCompareSnapshotsPlugin = require("cypress-visual-regression/dist/plugin");

module.exports = defineConfig({
  trashAssetsBeforeRuns: true,
  fixturesFolder: "test/cypress/fixtures",
  screenshotsFolder: "test/cypress/screenshots",
  videosFolder: "test/cypress/videos",
  reporter: "cypress-multi-reporters",
  reporterOptions: {
    configFile: "test/cypress/support/reporterOpts.json",
  },
  e2e: {
    // We've imported your old cypress plugins here.
    // You may want to clean this up later by importing these.
    setupNodeEvents(on, config) {
      getCompareSnapshotsPlugin(on, config);
    },
    specPattern: "test/cypress/integration/**/*.cy.{js,jsx,ts,tsx}",
    supportFile: "test/cypress/support/index.js",
    baseUrl: "https://develop-jcc-inyo.pantheonsite.io",
  },
  env: {
    failsilently: false,
    ALWAYS_GENERATE_DIFF: false,
    ALLOW_VISUAL_REGRESSION_TO_FAIL: false,
    SNAPSHOT_BASE_DIRECTORY: "test/cypress/screencaps/base",
    SNAPSHOT_DIFF_DIRECTORY: "test/cypress/screenshots/diff",
    INTEGRATION_FOLDER: "test/cypress/integration",
  },
});
