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
import './commands';
import addContext from 'mochawesome/addContext';

// Alternatively you can use CommonJS syntax:
// require('./commands')

// Clean up titles so they can be used in filenames.
function cleanTitle(title) {
  return title.replace(/[.,\/#!$%\^&\*;:{}=\-_`~()]/g,"")
}

Cypress.on('test:after:run', (test, runnable) => {
  // Add screenshots to report.
  const parentTitle = cleanTitle(runnable.parent.title);
  const title = cleanTitle(test.title);

  const screenshot = `../screenshots/${Cypress.spec.name}/${parentTitle} -- ${title} (failed).png`;
  addContext({ test }, screenshot);

  // Add videos to report.
  const video = `../videos/${Cypress.spec.name}.mp4`;
  addContext({ test }, video);
});
