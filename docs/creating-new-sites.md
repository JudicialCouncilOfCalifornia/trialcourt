# Creating New Site


## Spin up a pantheon instance and add deployment tooling

  - `scripts/pantheon_new.sh [site] "[Label]"`
    - i.e. `scripts/pantheon_new.sh kings "Judicial Council | Kings"
      - Keeping the Label consistently formatted helps to easily identify the site on Pantheon.
      - The Convention I've used is "Judicial Council | [site]" But capitalize the site.
    - **make sure [site] will pattern match to the identifying portion of the live url.**
      - i.e. www.kings.courts.ca.gov => `kings`

This script will create a new Pantheon instance, initialize the Live environment, create multidev environments develop and stage for parallel git workflow and update the tooling files in the repo, required for deploying and managing the new site.

After this runs it will give you instructions to set up the new Drupal instance locally.


## Build a New Drupal Instance Multisite

  - `lando multisite [name]`
    - make sure `[name]` will pattern match to the identifying portion of the live url. It should be the same as `[site]` from the `pantheon_new.sh` command above.
      - i.e. www.kings.courts.ca.gov => `kings`
    - Do initial configurations and export (@see Initial configurations)
    - Dump the database to import at Pantheon

## Initial configurations

  The above is all you need to do to have a working site with all the tooling for deployment.  2 commands, commit the code, and push.

  However, when tasked to spin up a new site, you can do some initial configuration.  (Some of this may be automated in the future.)

### Theme Settings

Configure theme at `/admin/appearance/settings/jcc_components`:
- Add the logo.svg to /web/sites/[site]/logo.svg
- Set the theme setting to not use default.
- Set the path to sites/[site]/logo.svg
- Set the various other theme settings. When in doubt, use the placeholders.

### Create node 1 to use as front page:

- Create Landing Page
  * Title: Home
  * Heading: Do Not Delete
  * Lead: Do not delete this node, it is configured as the home page.  Edit this node to update the homepage.

### Configure site and modules

- At `/admin/config/system/site-information`:
  * Site Name: `Superior Court of California | County of ...`
  * Site email: `no-reply@courtinfo.ca.gov`
  * Front page: `/node/1`  (or your home page if not node/1)
- Configure Bing Maps API Key (default for webservices):
  * `/admin/config/system/geocoder/geocoder-provider`
  * AsFHz4uyv4g8Kpx4IRQe31MzyAUqjfasyD5-96D-Im22cepaCQZTUIGC4Tku06e0
- Configure SendGrid:
  * `/admin/config/system/keys/manage/sendgrid`
  * See JIRA [ticket TCI-500](https://judasdg.atlassian.net/browse/TCI-500) for key.
  * Note: site's email address needs to be `no-reply@courtinfo.ca.gov` for SendGrid to work.
- Setup defaults for Google Tag module:
  * `lando drush @local.[site] cim --partial --source=/app/web/modules/contrib/google_tag/config/install/`

### Create users

- Create QA users with appropriate roles:
  - `lando drush @local.[site] ucrt [username] --mail=""`
  - `lando drush @local.[site] urol [role] [user,user,user]`
    - Roles: editor, manager, administrator, translator
- Alternately you can add these commands to a `scripts/users.sh` file like so:

```
#!/usr/bin/env bash

[ -z $NEW ] && NEW=$1

drush @local.${NEW} ucrt [name] --mail="[email]"
drush @local.${NEW} urol administrator [name,name,name]
```

This file is git ignored as it may vary from site to site, or based on what test users are needed.

### Final steps

- Verify everything is ok

- Uninstall dev modules: `devel stage_file_proxy features_ui twig_xdebug`  @see `require-dev` in `composer.json`.
  - NOTES:  Initial deploy fails because of missing dev modules that are in the split configuration. This is due to db dump of local which has these modules enabled, trying to run on an env that doesn't have those modules.
  - `lando drush @local.[site] pmu devel stage_file_proxy features_ui twig_xdebug`

- Export config
  - `lando drush @local.[site] cex -y`

- Export db from local
  - `lando drush @local.[site] sql-dump > data/[site]-install.sql`

- Import the initial database dump to live environment to prepare pantheon for deployment.

- Commit changes and deploy to appropriate environment(s) for testing and other pre-launch work. Live (master), is where content creation will happen pre-launch.

- Sync the db and files from live to develop and stage on Pantheon.
