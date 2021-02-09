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
import 'cypress-axe';

// Alternatively you can use CommonJS syntax:
// require('./commands')

// Clean up titles so they can be used in filenames.
function cleanTitle(title) {
  return title.replace(/[.\/#!$%\^&\*;:{}=_`~()]/g,"")
}

Cypress.on('test:after:run', (test, runnable) => {

  const parentTitle = cleanTitle(runnable.parent.title);
  const title = cleanTitle(test.title);

  if (test.state == 'failed') {
    // Add screenshots to report.
    const screenshot = `../screenshots/${Cypress.spec.name}/${parentTitle} -- ${title} (failed).png`;
    addContext({ test }, screenshot);
  }

  if (test.body.includes('matchImageSnapshot')) {
    const diffImage = `../snapshots/${Cypress.spec.name}/__diff_output__/${parentTitle} -- ${title}.diff.png`;
    addContext({ test }, diffImage);
  }

  // Add videos to report.
  const video = `../videos/${Cypress.spec.name}.mp4`;
  addContext({ test }, video);
});
