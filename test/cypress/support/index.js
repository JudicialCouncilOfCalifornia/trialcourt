// ***********************************************************
// This example support/index.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import "./commands";
import addContext from "mochawesome/addContext";
// import 'cypress-axe';

// Alternatively you can use CommonJS syntax:
// require('./commands')

// Clean up titles so they can be used in filenames.
function cleanTitle(title) {
  return title.replace(/[.\/#!$%\^&\*;:{}=_`~()]/g, "");
}

function cleanName(name) {
  return name.replace(/[.\/#!$%\^&\*;:{}=_`~(),]/g, "-").replace(/\s/g, "");
}

Cypress.on("test:after:run", (test, runnable) => {
  const grandparentTitle = cleanTitle(runnable.parent.parent.title);
  const parentTitle = cleanTitle(runnable.parent.title);
  const title = cleanTitle(test.title);

  if (test.state == "failed") {
    // Add screenshots to report.
    const screenshot = `${Cypress.config("screenshotsFolder")}/${
      Cypress.spec.name
    }/${grandparentTitle} -- ${parentTitle} -- ${title} (failed).png`;
    addContext({ test }, screenshot);
  }

  // if (test.code.includes("compareSnapshot")) {
  const name = cleanName(`${title}`).toLowerCase();
  const diffImage = `${Cypress.config("screenshotsFolder")}/diff/${
    Cypress.spec.name
  }/${name}.png`;
  addContext({ test }, diffImage);
  // }

  // Add videos to report.
  const video = `${Cypress.config("videosFolder")}/${Cypress.spec.name}.mp4`;
  addContext({ test }, video);
});
