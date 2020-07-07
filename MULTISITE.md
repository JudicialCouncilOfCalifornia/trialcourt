# MULTISITE CHANGELOG

## Codebase Changes

In preparation for multisite, `sites/default` will become a base install. We can use this for reviewing and updating the base installation. `tc.lndo.site`

Slo will live in `sites/slo`, and be accessed (with lando) at `tc-slo.lndo.site`.

The config directories will become `config/config-default` and `config/config-slo` respectively.

If you have a `config/config-local` you should rename that to `config/config-default-local`

The pattern will be `sites/[sitecode]` and `config/config-[sitecode]` where `[sitecode]` is a short abbreviation for the court. i.e. `slo`


## Features

This project uses Features module to share configuration across multisites, and with the installation profile. For detailed information about creating, managing and deploying features, please see the [FEATURES.md](FEATURES.md).


## Lando Changes

Lando will have a proxy and a database service for each multisite, including default. It will require a `lando rebuild` to activate, each time a proxy and datbase service are added.  See `proxy` and `services` in the `.lando.yml` file for examples.

### `lando fresh` also changes to accommodate multisite.

You must now indicate which site to run on with the `-l` option. The choice of `-l [URI]` is to be consistent with drush commands.

To import a database use the (optional) `-f [path/to/file.sql|gz]`.

i.e `lando fresh -l tc-slo.lndo.site -f data/slo-database.sql.gz`

### `lando multisite`

There's a new command for starting multisites. `lando multsite [sitecode]`

Before you run this you should edit the `.lando.yml` file to add a proxy and a db service, then run `lando rebuild` to install them.

Once those are enabled `lando multisite [sitecode]` will perform the rest of the steps to install the multisite on your local by cloning the default sites directory, and updating certian strings in settings file. It then runs `drush site-install` for you. There is one prompt to make sure you're ready to install.

Installation on my local takes about 10 minutes. There is no progress indicator on the commandline installer.

----

## Multisite Creation: All Steps

 - create new proxy in .lando.yml tc-[new].lndo.site
 - create new db service in .lando.yml db[new]
 - `lando rebuild`

 - `lando multisite [new]`  ~10min Does the following.
   - copy `sites/default` to `sites/[new]`
   - delete `sites/[new]/files`
   - mkdir `config/config-[new]-local`
   - A series of pre install string changes for settings files.
   - `drush si -l tc-${NEW}.lndo.site -vvv --site-name="SITE NAME" --account-mail="jcc@example.com" --account-name="JCC" --account-mail="jcc@example.com`

   - POST INSTALL
     - Config export from the installed site.
     - A few more string replacements to key config files, updating site directories.

## Pantheon Multisite Hackery!

I've managed to make Pantheon run Drupal Multisite... sort of.

There are 2 key tricks to this.

One is to set the files directories for each multisite in settings.php for a subdirectory in the default files directory and NOT in `/sites/*/files/`.  i.e.

`/sites/default/files`
`/sites/default/files/slo`
`/sites/default/files/oc`
etc.

The reason for this is that in test and live environments, nothing outside of `/sites/default/files` is writable.

The second issue is the limitation of one database per environment. You COULD use a table prefix configured for each subsite. Pantheon does not recommend this, but when have I ever listened to Pantheon's recommendations? However, this is actually a pain, especially if you ever need to separate the sites in the future. But, it could work.

For our use, we want to use one Drupal Multisite codebase for local development, but we're limited by Pantheon and stuck there for the time being. So we can kind of virtualize a multisite across multiple Pantheon project instances.

 - We have one Drupal codebase. For local development it's just a standard multisite. (Though we are technically sharing the default files directory).
 - We have a Pantheon project for each court.
 - Our sites.php file is configured with the appropriate urls pointing to the appropriate site directories. i.e.:
   - develop-jcc-oc.pantheonsite.io -> oc
   - develop-jcc-napa.pantheonsite.io -> napa
 - Circle CI is configured to build_test the codebase, then deploy it to multiple Pantheon instances. Due to their configuration, it just works.
 - Each additional site requires one run command in the CI config.yml and a project-*.sh config file to complement the deploy.sh script.
   - `run: command: command: .circleci/scripts/deploy.sh oc`
   - `.circleci/scripts/project-oc.sh`
 - `project-*.sh` sets 3 variables and is imported by the main deploy.sh script.
   - `UUID=` - the pantheon project UUID.
   - `SITE_CODE=` - the abbreviated site code i.e. slo, oc, napa.
   - `LIVE=[true|false]` - if set to true, the deploy of `master` will get a pantheon live tag.

### Additional notes about managing multisite.

 - Each multisite needs a drush alias file in `drush/sites`. Copy one and update the UUID in the entries.
 - Remember to add environment urls to sites.php for each environment pointing to the appropriate multisite directory.
 - When creating a new multisite, export the local database and import it to the necessary Pantheon environment(s).
