# GitOps Workflow and Deploying Code

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

This repo is managed on GitHub so push your feature there and make a Pull Request to `develop`.  This will be merged and deployed to the develop (multidev) environment on Pantheon for INTERNAL QA.

If that passes, merge the clean FEATURE branch into `stage` for deployment to the stage (multidev) environment on Pantheon for CLIENT review/approval.

If it's approved for deployment to production, merge the clean FEATURE branch into `master` for deployment to the Live environment on Pantheon.

In this way, individual features can move through the environments without affecting, or being affected by, other work in progress. Issues can stall in any environment for any reason and not hold up the progress of any other issues in development.  Hotfixes and security updates can breeze through without having to worry about whether or not you can deploy the 5 other things that might already have been sitting on stage for the last 3 months.

At the end of a sprint (or whenever it seems necessary) the `develop` and `stage` branches can be scrapped and started clean again by branching from master.

## Alternative Release Options

 - **Single Feature/Hotfix**: Deploy a single approved feature branch as a production release by merging it into the master branch.
 - **Release Branch**: As features are approved on `stage` they can be merged into a new `release-x.y.z` branch that was cut from the clean `master` branch. Do not merge any un-approved feature branches into the release branch. Deploy the `release` branch whenever it's ready by merging it into the `master` branch.
 - **Completed Stage**: You can consider `stage` a release branch if you're diligent in confirming all features merged into it are approved and signed off, then merging `stage` into `master` to deploy it as a release. This has some risk of deploying code that may have made it to `stage` but has not been signed off for release, so be careful.

## Testing and Approval

A **solo dev** with limited oversight could be trusted to approve at the develop level, so the develop environment step may not be necessary. Leave it in place for when you need more detailed internal review.  Just remember that stage and Live will get ahead of it if you don't use it for every feature, so you'll need to merge `master` into `develop` first, if you want a clean and concise Pull Request into develop.

Features should always be approved by the product owner on stage. Avoid doing internal review on stage because if it fails, stage would be contaminated with a feature not ready for product owner review which can be confusing to the product owner or PM.

Though a solo dev may be trusted to deploy a feature or update without approval, requiring approval and signoff is recommended to help spot issues we may have blind spots for, and to protect us from breakage on production.
