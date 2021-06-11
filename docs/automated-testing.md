# Automated Testing

 - PHP linting on CI build_test - enabled
 - PHP Code Sniffer on CI build_test - runs `composer -n code-sniff` but only returns a message. @see composer.json scripts section.
 - PHP Unit on CI build_test - runs `composer -n unit-test` but only returns a message. @see composer.json scripts section.
 - PHP Code Sniffer on git pre-commit hook installed with composer.
 - Cypress testing is installed and enabled for Visual Regression and End to End testing. See [Cypress Testing](#cypress-testing) below.

There is lando tooling for phpcs, phpcbf and phpmd. Just pass in a file or directory.

i.e. `lando phpcbf web/modules/custom/my_module`

When you commit code `phpcs` runs and will show you a detailed list of standards violations and php errors in the committed files. Please clean them up before committing code to the repo. `phpcbf` can be used to automatically clean up many minor code style issues.

## Cypress

https://docs.cypress.io

Cypress is installed and runs on CI/CD processes, but can also be used for development testing.

The tests in CI run "headless" and generate json data for reports. When you run Cypress locally you get a GUI that shows the tests running in real time and once the test finishes you can "time travel" through the whole test to see exactly what's happening and when, as it takes a snapshot of the whole DOM at each step.

Cypress is written in javascript and has the benefit of many packages on NPM.

The package.json for cypress lives in the project root and the tests and config live in `test/cypress`.

Cypress is extremely powerful and I highly recommend you read the excellent documentation. I'll outline how it's used in this project below.

There's a script that's called in the CircleCI workflow that looks for certain test specs to run automatically when deploying to test environments.

`.circleci/scripts/cypress-ci.sh`

Cypress test specs are written in Javascript so it's easy to learn and very flexible. The `cypress-ci.sh` script looks in the `test/cypress/integration` directory for spec files named with the following patterns:

  - `[site]-ci.spec.js`
  - `[site]-vrt.spec.js`

Where `[site]` matches the site directory name in `web/sites/`.

The `[site]-ci.spec.js` file can be used to define each site's critical end to end or behavioral tests. A simple one, might be to check if your front page shows all the sections you expect, or doesn't show any error messages, every time you deploy to production. But you can also do anything a user can do in a browser, so you can test end to end user workflows like navigating through the menu to a page, logging in, filling in a form, reading a success/fail message, etc.

Creating a few tests to check for critical things on deployment can help catch important issues before they impact users.

The `[site]-vrt.spec.js` file is for Visual Regression Testing. CI will do a base snapshot of pages from the live site and then compare those, pixel by pixel to a snap shot of the environment being deployed. It will fail if some threshold of difference is crossed. This is fully configurable. You can even ignore certain elements on a page that you know are dynamic.

### Reports

The `cypress-ci.sh` script use mochawesome to generate HTML report from data compiled during the test runs. This data and the report are stored as artifacts attached to the job in CircleCI.  You can find them by navigating to the job and then the Artifact tab for that job. The report is `reports/cypress-report.html`

This will include test Pass/Fail in an easy to scan format, but you can open each test to see more detail. The details include any error messages, a snap shot of the fail state, a video capture of the tests running and in the case of VRT the snapshots and visual diff between the base and the deployed environment.

The script also generates a Slack message with links to the reports and delivers it to the Slack API via the SLACK_WEBHOOK environment variable.

### Feature Testing

It's impractical to run 100% test coverage on deployments. But it's helpful to leverage Cypress End to End testing for testing new features during development. It's too time consuming to manually test to see if a new feature installed correctly and functions properly across a growing list of sites supported by this platform. Writing a feature test spec can easily cover every site once the feature has been synced to the fleet. It doesn't have to run during CI, it can be run manually from a developer's local on any environment, just as an integrity check for that deployment.

## Additional Test Types and Packages

You might want to install AXE integration with Cypress to test for accessibility.  https://www.npmjs.com/package/cypress-axe/v/0.12.0

Or even Lighthouse for performance testing. https://www.npmjs.com/package/cypress-lighthouse

