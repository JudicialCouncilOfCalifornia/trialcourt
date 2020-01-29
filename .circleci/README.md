# CircleCI Configuration Notes

The implementation of CI/CD workflow when you move to Azure will depend on choices you make for developer workflow, test environments and technology. Azure Cloud has it's own Pipelines you may choose instead of CircleCI.

Each CI platform has thorough documentation, but I'll describe the general concepts currently employed on this project.

## General Concepts

A CircleCI pipeline is configured via `config.yml` in the `.circleci` directory of this project.

It configures a docker image that is representative of the environement we'll deploy to. In this case `quay.io/pantheon-public/build-tools-ci:6.x`. You'll want to update this if you move to a different host configuration.

It sets some variables used later in the steps.

It defines jobs consisting of steps. Steps are keyed with commands `run`, `save_cache`, `restore_cache`, etc.

 * `run`: can define a command which executes like the commandline inside the build container. Often this simply calls scripts included in the `.circleci` directory, to do the heavy lifting.
 * `save_cache`: define a key and a path to make accessible later in a workflow, so you can avoid having to generate their contents again in the same workflow, to save some time. (i.e. `$HOME/.composer/cache`)
 * `restore_cache`: loads the caches saved previously in the workflow.

It defines a workflow `build_and_test` that runs the jobs defined previously, with conditions (on which branches) and requirements (deploying code requires a succesful build and test first).

### Configuring Github and Host

For the containers used by CircleCI to access git repos and execute commands on your Github repo and Hosting platform, you need to configure some environment variables in the CircleCI UI for your project. This keeps sensitive information out of the repo.

For access to Drupal, Github and Pantheon, these are currently:
```
ADMIN_EMAIL
ADMIN_PASSWORD
GITHUB_TOKEN
GIT_EMAIL
ARTIFACT_GIT
```

## Builds, Test, Deploy

On the creation of a Pull Request (PR) on Github, CircleCI will build and test the codebase from the feature branch. When CircleCI is triggered, is configured in the settings for your project on Github.

My preference is to design a pipeline that limits dependency on anything other than fundamental tools.

For a Symfony application like Drupal we want a docker image that matches our production environment for build and automated testing. We want to include git and composer.

The workflow/pipeline could consist of 3 jobs, `build`, `test`, and `deploy`.

`build` will spin up the environment and run `composer install` to build Drupal and compile your theme if necessary. This should all be automated in the `composer.json` file.

If the `build` job passes, `test` will run any commands/scripts for code linting and automated testing.

If `test` passes, it will notify Github that the PR is ok to merge.

These jobs will also run when an environment branch is updated by the merge of a PR or if someone manually pushes changes to the environment branch.  Environment branches may include `develop`, `stage`, and `master`, or perhaps only one, depending on your git branching strategy and workflow.

When the pipeline runs on an environment branch, the `deploy` job can run if the `build` and `test` jobs pass.

## Deploying an Artifact

When deploying a build we do not want to deploy everything in the working Github repo to the environment. We only want to deploy the parts of the repo that make the working application.

A simple process for doing this is defined in the `.circleci/scripts/deploy.sh` file. This is currently written in BASH, but could easily be converted to PHP for more familiarity to PHP developers, or even JS if this were a Node project.

Rather than depending on tooling specific to a hosting platform like Pantheon/Terminus to do the work, we can use basic tools available to any linux container. In this case git and rsync.

At this point in the build pipeline we already have our test build. All the deploy script needs to do is:

 * `git clone` the git repo from the environment (the artifact)
 * `git checkout` the correct environment branch in the artifact.
 * `rsync` the correct files from our working repo (on the matching environment branch) to the artifact.
 * `git add` and `git commit` the changes.
 * `git push` the artifact to the environment.

That's essentially it. This could be done with platform integrated tooling like `terminus` or even via commands in the CircleCI `config.yml`, but if we use basic scripts, moving the project somewhere else is simple. Even retooling the CI config is just a matter of telling the new CI platform to run the scripts we already have.

You could even deploy the project from your local with one command.

### Post Deploy

Depending on your hosting platform, certain things may need to occur in order to deploy your new code to an environment. For example, code doesn't run directly from the git repository on the host. Code syncs to a `work_tree`.

In the case of Pantheon, code is deployed to multidev environments based on branch name and standard environments based on tags.

On Acquia you can set whether an environment syncs code from a branch or a tag.

If you're running your own servers you can achieve code sync to a `work_tree` with a `post-recieve` git hook.

After deploying a Drupal project we want to run database updates, config import and cache rebuild. There is more than one approach you can take with this.

If your project has drush aliases configured for your hosting platform, you can add the drush commands to the deploy script or CI config file for:

 * `drush @[site.env] updb -y` Database Updates
 * `drush @[site.env] cim -y` Config Import
 * `drush @[site.env] cr` Cache Rebuild

### The Current Configuration for Pantheon

The repo is configured for "parallel testing environments". In this case, `develop` and `stage` are Multidev environments and code is synced based on branch name. `master` is the production branch and new releases will be synced to the Live environment with a `pantheon_live_N` tag.

On these three branches, post deploy commands are run with drush aliases in the deploy script.

## Closing

Configuring your CI/CD pipeline can depend on a number of factors and preferences. The configuration may change during it's life, in order to better serve the evolving needs of the project, but keeping it simple and platform agnostic makes it very easy to port to new tooling and environments, plus makes build/test less error prone.
