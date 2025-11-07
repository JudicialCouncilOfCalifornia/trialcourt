# MULTISITE

**NOTE:** See [creating-new-sites.md](./creating-new-sites.md) for how to spin up a new site. This document only describes how multisites are configured. Don't miss the part on Pantheon at the end!

Sites live in `sites/[site]`, and are accessed (with lando) at `[site].lndo.site`.

The config directories are `config/config-[site]` and `config/config-[site]-local`


## Features

This project uses Features module to share configuration across multisites, and with the installation profile. For detailed information about creating, managing and deploying features, please see the [feature-config-management.md](./feature-config-management.md).


## Lando Changes

Lando no longer needs a proxy and a database service for each multisite.
Instead we use a wildcard proxy "*.lndo.site" to catch them all and we create multiple databases in the single database service.

It will no longer require a `lando rebuild` to activate a new multisite. The multisite installer simply creates a new database for the site in the single database container.

There is tooling and a helper script for this. `scripts/dbcreate.sh` runs on `lando start|rebuild` and creates a database for every directory in `/app/web/sites/`. It also creates a new database when `lando multisite` is run.

### `lando fresh` also changes to accommodate multisite.

You must now indicate which site to run on with the `-l` option. The choice of `-l [URI]` is to be consistent with drush commands.

To import a database use the (optional) `-f [path/to/file.sql|gz]`.

i.e `lando fresh -l slo.lndo.site -f data/slo-database.sql.gz`

NOTE: This still works with the database changes mentioned above.

### `lando multisite`

There's a new command for starting multisites. `lando multsite [site]`

You no longer need to edit the `.lando.yml` file to add a proxy and a db service, or run `lando rebuild`.

Simply run `lando multisite [sitecode]` and it will perform all of the steps to install the multisite on your local by cloning the default sites directory, and updating certain strings in settings file. It then runs `drush site-install` for you. This no longer prompts you to drop the database. It assumes yes, so you can run the command and take a nap or something.

Installation on my local takes about 10 minutes. There is no progress indicator on the commandline installer.

----

## Multisite Creation: All Steps

 - `lando multisite [new]`  ~10min Does the following.
   - copy `sites/default` to `sites/[new]`
   - delete `sites/[new]/files`
   - mkdir `config/config-[new]-local`
   - A series of pre install string changes for settings files.
   - `drush si -l ${NEW}.lndo.site -vvv --site-name="SITE NAME" --account-mail="jcc@example.com" --account-name="JCC" --account-mail="jcc@example.com`
   - If you have a `scripts/users.sh` it will run that to create users.
   - POST INSTALL
     - Config export from the installed site.
     - A few more string replacements to key config files, updating site directories.

Example `scripts/users.sh` file, (update to add the users and assign the roles you want):

```
#!/usr/bin/env bash

[ -z $NEW ] && NEW=$1

drush @local.${NEW} ucrt Steph --mail="steph.doe@jud.ca.gov"
drush @local.${NEW} ucrt Michael --mail="michael.doe@jud.ca.gov"
drush @local.${NEW} urol administrator Steph,Michael
```
---


## Pantheon Multisite Hackery!

I've managed to make Pantheon run Drupal Multisite... sort of.

There are 2 key tricks to this.

One is to set the files directories for each multisite in settings.php for a subdirectory in the default files directory and NOT in `/sites/*/files/`.  i.e.

  - `/sites/default/files`
  - `/sites/default/files/slo`
  - `/sites/default/files/oc`
etc.

The reason for this is that in test and live environments, nothing outside of `/sites/default/files` is writable.

The second issue is the limitation of one database per environment. You COULD use a table prefix configured for each subsite. Pantheon does not recommend this, but when have I ever listened to Pantheon's recommendations? However, this is actually a pain, especially if you ever need to separate the sites in the future. But, it could work.

For our use, we want to use one Drupal Multisite codebase for local development, but we're limited by Pantheon and stuck there for the time being. So we can kind of virtualize a multisite across multiple Pantheon project instances.

 - We have one Drupal codebase. For local development it's just a standard multisite. (Though we are technically sharing the default files directory).
 - We have a Pantheon project for each court.
 - Our sites.php file is configured with the appropriate urls pointing to the appropriate site directories. i.e.:
   - develop-jcc-oc.pantheonsite.io -> oc
   - develop-jcc-napa.pantheonsite.io -> napa
   - Actually, this is partly true. We can manually create these mappings if we need too, but there's a pattern match in `sites.php` now that will just direct to the correct path if it detects that path name in the url.  i.e.
     - `www.napa.courts.ca.gov` -> `sites/napa` because it detects `.napa.`.
 - Circle CI is configured to build_test the codebase, then deploy it to multiple Pantheon instances. Due to their configuration, it just works.
 - Each additional site only requires it's name be added to the "matrix" array in CI config.yml and a project-*.sh config file to complement the deploy.sh script. **This is all done automatically by the [creating new sites process](./creating-new-sites.md).** You don't need to do it manually.
   - `site: ["colusa", "inyo", "slo2", "sc", "store-front", "tehama", "lake", "kings", "humboldt", "sierra", "tularesuperiorcourt", "mendocino", "alpine", "siskiyou", "mono", "napa", "supremecourt", "madera"]`
   - `.circleci/scripts/project-oc.sh`
 - `project-*.sh` sets 4 variables and is imported by the main deploy.sh script.
   - `UUID=` - the pantheon project UUID (Find it in the url for the dashboard of that project.)
   - `SITE_CODE=` - the abbreviated site code i.e. slo, oc, napa.
   - `LIVE=[true|false]` - if set to true, the deploy of `master` will get a pantheon live tag. Otherwise, master will be viewable on the standard pantheon "Dev" environment, not to be confused with our multidev `develop` testing environment.
   - `DEPLOY_MASTER=[true|false]` - if set to false, the deploy of the `master` branch will skip deployments.

### Additional notes about managing multisite.

 - Each multisite needs a drush alias file in `drush/sites`. Copy one and update the UUID in the entries as well as the URI. UUID is found on the pantheon dashboard for that project. It's the long id string in the url. **Again, this id done for you automatically.**
 - Remember to add environment urls to sites.php for each environment pointing to the appropriate multisite directory. **Automatic!** This is only necessary for custom domains that do not have the site name pattern. See sites.php for more info.
 - When creating a new multisite, export the local database and import it to the necessary Pantheon environment(s) so we have compatible config import/export moving forward.
