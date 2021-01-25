# Automated Testing with Cypress

## Official Docs

https://docs.cypress.io/guides/overview/why-cypress.html


## CI Integration

Select tests are integrated into the CI/CD pipeline. See the README.md in .ci or .circleci directories for more info.


## Testing Locally and Writing Tests

Cypress tests are written in Javascript and live in `tests/cypress/integration`. See the official docs for more info. There is an directory, `tests/cypress/examples`, that contains the examples that come with a new install of Cypress. You can use those for reference as well.

### Headless Cypress

Cypress will run "headless" during CI automated testing. You can run the same tests locally inside docker using lando. `lando npm run cy:run`

To run specific tests you can use the `--spec` option.

`lando cy:run -- --spec cypress/integration/my.spec.js`

### Cypress UI

The Cypress UI cannot be run from inside docker as it needs to access your local browser. So, to use this we must run `npm run cy:open` from our local terminal.


## Visual Regression Testing with Cypress: Cypress Image Snapshot

Plugin
https://github.com/jaredpalmer/cypress-image-snapshot

Tutorial Blog Post
https://medium.com/norwich-node-user-group/visual-regression-testing-with-cypress-io-and-cypress-image-snapshot-99c520ccc595

