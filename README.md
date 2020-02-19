# Readme

## Meta

This document should be updated by *YOU*, as necessary as the project evolves.
Add your author information for historical reference and professional context, as:
 - `Date first edited: NAME (email)`


### Authors:
 - 2020-01-10: Calvin Tyndall (calvin@chapterthreellc.com)

### Project Info:
 [repo]: https://github.com/JudicialCouncilOfCalifornia/trialcourt
 [host]: https://pantheon.io
 [ci]: https://circleci.com
 [backend]: https://drupal.org/8
 [frontend]: https://drupal.org/project/jcc_base
 [parallelpdf]: ParallelPantheon.pdf

 - Working Repo: [Github][repo]
 - Hosting: [Pantheon][host]
 - CI/CD: [CircleCI][ci] - [![CircleCI](https://circleci.com/gh/JudicialCouncilOfCalifornia/trialcourt/tree/master.svg?style=svg)](https://circleci.com/gh/JudicialCouncilOfCalifornia/trialcourt/tree/master)
 - Backend: [Drupal 8][backend]
 - Frontend: [Drupal 8 Theme - JCCBase][frontend] subtheme(s).

### Contents

 - [Local Development](#user-content-local-development)
 - [Module Management](#user-content-module-management)
   - [Adding Contrib Modules](#user-content-adding-contrib-modules)
   - [Updating Core](#user-content-updating-core)
   - [Updating Contrib Modules](#user-content-updating-contrib-modules)
   - [Removing Contrib Modules](#user-content-removing-contrib-modules)
   - [Applying Patches](#user-content-applying-patches)
 - [Git Workflow and Deploying Code](#user-content-git-workflow-and-deploying-code)
   - [Parallel Workflow](#user-content-git-workflow-and-deploying-code)
   - [Testing and Approval](#user-content-testing-and-approval)
   - [Automated Testing](#user-content-automated-testing)
   - [Deploying Code](#user-content-deploying-code)
 - [Troubleshooting Local Environment](#user-content-troubleshooting-local-environment)

## Local Development

* Docker: https://www.docker.com/community-edition
* Lando: https://docs.devwithlando.io

Lando/Docker optional, any *AMP environment will do, but there is helpful tooling here for Lando.

**WARNING: Docker for Mac 2.2 currently breaks Lando. Use the Lando installer to install it's preferred version of Docker.**

Once installed cd to project directory and type `lando` for a list of commands.

*Spin up the local:*

 - `lando start` - Spin up the environment.
 - `lando db-import [path to db]` - Import your database. Store db in `data/`.
 - `lando composer install` - Composer install.
 - `lando th` - "Theme: Help" for detailed info on managing multiple themes.
 - `lando reset` - Runs updb, cim, cr ...

Composer install will move `settings.local.php` and `services.local.yml` to the `sites/default/` directory with functional configuration.  You can replace or alter these files if you wish. The will not be replaced if they exist.

**Ready to work.**

Database can be fetched in multiple ways. I recommend saving to `data/` directory which is ignored and within the environment so it can be saved for future resets and imported from there, whithout cluttering up the project root with different versions of the database.

Use drush alias with dev (not develop!) as our stand in for live, pre-production:

`lando drush @p.dev sql-dump > data/dev-YYYY-MM-DD.sql`

#### Options:

[env] = live or the most live like environment.

 - Terminus
   - terminus backup:create jcc-tc.[env] --element=db
   - terminus backup:get jcc-tc.[env] --element=db
 - Drush Alias
   - lando drush @self.[env] sql-dump --gzip > data/[env]-YYYY-MM-DD.sql.gz
 - Pantheon Dashboard
   - Select the environment tab or multisite
   - Select the Backups vertical tab
   - Download the latest database backup

## Module Management

From the project root:

### Adding Contrib Modules

 - `lando composer require drupal/[package_name] --no-update` to add it to the composer.json without updating everything.
 - `lando composer update drupal/[package_name] -o` to fetch/update only the desired module.

### Updating Core

- `lando composer update drupal/core drupal/core-recommended --with-dependencies -o`

### Updating Contrib Modules

 - `lando composer update drupal/[package_name] -o`

Sometimes several contrib modules are several versions behind.

*Do not use `lando composer update` without specifying a module, or it will update everything that's outdated at once, possibly introducing regressions which you'll have to do much more testing for.*

*Updates should be controlled and tested well. It's easiest to do that in smaller chunks. Especially watch out for BETA, ALPHA, or DEV versions of modules which are not stable and make no guarantees about not breaking things between updates. Ideally, never use Alpha/Dev modules and use BETA's sparingly. Consider contributing to the project to help get it to a full release.*

### Removing Contrib Modules

Enabled modules should be removed from a code base in 2 separate releases. The first release update should simply uninstall the module. The second release should remove the module from the codebase as described below. If you do it all at once Drupal will not be able to find the module code to be able to uninstall it, because it won't exist anymore.

Phase 1: Uninstall the module:

 - `lando drush pmu [module]` - uninstall the module.
 - `lando drush cex` - export the config changes caused by uninstalling the module.
 - Deploy the changes to update the Production site.

Phase 2: Remove the module:

 - `lando composer remove [package] --no-update` will remove a package from require or require-dev, without running all updates.
 - `lando composer update [package] -o` will remove the package code.

### Applying Patches

If you need to apply patches, you can do so with the
[composer-patches](https://github.com/cweagans/composer-patches) plugin.

To add a patch to drupal module `foobar` insert the patches section in the extra
section of composer.json:
```json
"extra": {
    "patches": {
        "drupal/foobar": {
            "Patch description": "Drupal URL to patch"
        }
    }
}
```

## Git Workflow and Deploying Code

**NOTE: Always reset your local to a production like state before starting a new feature. Configuration should be imported from `master` and/or a production database should be imported before starting work, so that config changes from the new feature are clean when exported.**

This project is configured for a [Parallel Git Workflow on Pantheon][parallelpdf] using multidev environments.

ENV -> GIT BRANCH

 - develop (multidev) -> `develop`
 - stage (multidev) -> `stage`
 - Live (default pantheon live) -> `master`

In this way `master` is ALWAYS clean production code.

New feature branches for any work should be branched from `master` so it starts clean.  If you branch from anything else, you will carry in code that's not related to your ticket that can be hard to separate for deployment to Production if your code is approved, but the other code is not. Your branch will be "contaminated". Stay clean to not accidentally introduce rejected code to production and to not frustrate whoever needs to deploy project code with precision.

`feature/[ticket-id]--short-description` or `feature/TC-27--project-repo-base-theme`

Lead commit messages with ticket id: **TC-27: Message ...**

This repo is managed on GitHub so push your feature there and make a Pull Request to `develop`.  This will be merged and deployed to the develop (multidev) environment on Pantheon for INTERNAL review/QA.

If that passes, merge the clean FEATURE branch into `stage` for deploy to the stage (multidev) environment on Pantheon for CLIENT review/approval.

If it's approved for deployment to production, merge the clean FEATURE branch into `master` for deploy to the Live environment on Pantheon.

In this way, individual features can move through the environments without affecting, or being affected by, other work in progress. Issues can stall in any environment for any reason and not hold up the progress of any other issues in development.  Hotfixes and security updates can breeze through without having to worry about whether or not you can deploy the 5 other things that might already have been sitting on stage for the last 3 months.

#### Alternative Release Options

 - **Single Feature/Hotfix**: Deploy a single approved feature branch as a production release by merging it into the master branch.
 - **Release Branch**: As features are approved on `stage` they can be merged into a new `release-x.y.z` branch that was cut from the clean `master` branch. Do not merge any un-approved feature branches into the release branch. Deploy the `release` branch whenever it's ready by merging it into the `master` branch.
 - **Completed Stage**: You can consider `stage` a release branch if you're diligent in confirming all features merged into it are approved and signed off, then merging `stage` into `master` to deploy it as a release. This is some risk in deploying code that may have made it to `stage` but has not been signed off for release, so be careful.

### Testing and Approval

A **solo dev** with limited oversight could be trusted to approve at the develop level, so the develop environment step may not be necessary. Leave it in place for when you need more detailed internal review.  Just remember that stage and Live will get ahead of it if you don't use it for every feature, so you'll need to merge `master` into `develop` first, if you want a clean and concise Pull Request into develop.

Features should always be approved by the product owner on stage. Avoid doing internal review on stage because if it fails, stage would be contaminated with a feature not ready for product owner review which can be confusing to the product owner or PM.

Though a solo dev may be trusted to deploy a feature or update without approval, requiring approval and signoff is recommended to help spot issues we may have blind spots for, and to protect us from breakage on production.

### Automated Testing

 - PHP linting on CI build_test
 - PHP Code Sniffer on CI build_test
 - Other automated testing may be added and is encouraged

### Deploying Code

Merging a PR or pushing new commits to one of the environment branches on Github (develop, stage, master), will trigger CircleCI to build, test and deploy the updated branch to it's corresponding environment.

It does this by syncing the production code from the working repo, to a separate production repo on the host.

Quicksilver scripts should run post deploy drush commands like `updb -y`, `cim -y`, `cr`.  Make sure you check this for failure and run manually if needed.

@todo: use drush aliases from CI instead of relying on Quicksilver which is Pantheon specific and fails silently.

This process keeps working code separate from production code.

