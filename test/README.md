# Automated Testing with Cypress

## Official Docs

https://docs.cypress.io/guides/overview/why-cypress.html


## CI Integration

Cypress testing has been added to test integrations. It runs AFTER deployment to a test or live environment and generates reports for quality assurance. Failed End to End tests will NOT prevent deployment to an environment. Its purpose is to test integration of new code on test environments so you can catch things early in the QA process. It runs on production as a final smoke test, just in case.

Only select tests are integrated into the CI/CD pipeline.

`.circleci/scripts/cypress-ci.sh [-b -e [env]] sitename sitename sitename` to test the CI script locally.

`sitename` = the site you want to run tests for i.e. newsroom md ...
**NOTE**: tc is SLO.

`-b sitename sitename ...` = runs only the base snapshots for Visual Regression Testing. This should be run first.

`-e [env] sitename sitename ...`: [env] is the environment branch to test. `develop`, `stage`, `master`. **For local purposes only. In CI the env is determined by the $CIRCLE_BRANCH which maps to the environment.**

Example:
  - `.circleci/scripts/cypress-ci.sh -b newsroom tc` - first set the base snapshots from live for newsroom and slo.
  - `.circleci/scripts/cypress-ci.sh -e develop newsroom tc` - run tests for newroom and slo on the develop env.

The CI script currently expects tests named `sitename-ci.spec.js` and `sitename-vrt.spec.js`. Any tests that should be run during CI should be named this way.

This is to allow us to run a select set of tests for CI automation, and still develop other tests to aid in development that don't necessarily run on every deployment. CI config is structured to run Cypress tests on deployments to test environments, as well as master/live. We can control which multisites are run. Failing tests do not prevent deployment to the environment, as they happen after the deployment step.

Reports are available in the "Artifacts" section on Circle CI for the run. A slack message can be sent if a webhook is present in the environment variables (SLACK_WEBHOOK).  **Note**: currently the web hook it wants is in CALVIN_SLACK while I work out the kinks. @todo fix this in cypress-ci.sh.

A HTML Report in the `reports` directory includes a log of all tests run along with snapshots (when they fail) and videos of each test running.

## Testing Locally and Writing Tests

Cypress tests are written in Javascript and live in `tests/cypress/integration`. See the official docs for more info. There is an directory, `tests/cypress/examples`, that contains the examples that come with a new install of Cypress. You can use those for reference as well.

You can run specific tests locally with:
 - `npm run cy:open` - to run with the GUI
 - `npm run cy:run` - to run headless like it would in CI.

 With `cy:run` you should include `--spec test/cypress/my-test.spec.js` for the test you want to run, otherwise it runs everything. If you run `cy:open` you can choose what test to run in the GUI.

 Also include `--config baseUrl=https://your-local-url` to test locally.

 Examples:
 - `npm run cy:open -- --config baseUrl=http://slo.lndo.site`
 - `npm run cy:run -- --config baseUrl=http://slo.lndo.site --spec my-test.spec.js`

Don't forget the `--` to separate the arguments from the npm run command.


## Visual Regression Testing with Cypress: Cypress Image Snapshot

Plugin https://github.com/jaredpalmer/cypress-image-snapshot
