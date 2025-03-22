# Creating New Site


## Spin up a pantheon instance and add deployment tooling

- `scripts/pantheon_new.sh [site] "[Label]"`
  - i.e. `scripts/pantheon_new.sh kings "Judicial Council | Kings"`
    - Keeping the Label consistently formatted helps to easily identify the site on Pantheon.
    - The Convention I've used is "Judicial Council | [site]" But capitalize the site.
  - **make sure [site] will pattern match to the identifying portion of the live url.**
    - i.e. www.kings.courts.ca.gov => `kings`
    - If the site name ends up different from the subdomain or has alternate/multiple subdomains, add a mapping to /web/sites/sites.php. Without the mapping, the site will not load 100% correctly due mainly to incorrect paths missing the site name.
      - $sites['www.diffname.courts.ca.gov'] = 'site';
      - $sites['diffname.courts.ca.gov'] = 'site';

This script will create a new Pantheon instance, initialize the Live environment, create multidev environments develop and stage for parallel git workflow and update the tooling files in the repo, required for deploying and managing the new site.

After this runs it will give you instructions to set up the new Drupal instance locally.


## Build a New Drupal Instance Multisite

- `lando multisite [name] [theme]`
  - Make sure `[name]` will pattern match to the identifying portion of the live url. It should be the same as `[site]` from the `pantheon_new.sh` command above.
    - i.e. www.kings.courts.ca.gov => `kings`
  - You can optionally specify an alternate `[theme]` from the JCC Elevated default, specifically to use JCC Components (jcc_components) for the current trial court themes & features.
  - If the new site creation failures such as database connection or missing configurations, delete the `web/sites/[name]` and remedy the problem that is reported before re-executing the `multsite` command.
- For elevated sites:
  - Install the following manually until we can automate or consolidate themes:
    - `lando drush en jcc_elevated_custom -l @local.[site]` - customizations only for elevated sites.
    - `lando drush en jcc_elevated_embeds -l @local.[site]` - embeddable views/features.
    - `lando drush en jcc_elevated_sections -l @local.[site]` - if needed to create content division.
  - Set 'JCC' starter user role as administrator else you won't be able to administer menu structures. The password is noted in the lando multisite dialog else create yourself an admin account.
- Confirm `web/sites/[site]` setup (extra files and directory seems okay to keep or delete)
  - .gitignore
  - services.local.yml
  - settings.azure.php
  - settings.pantheon.php
  - settings.php
    - Remove the following configurations if present:
      ```
      $databases['default']['default'] = array (
        'database' => 'diversity-toolkit',
        'username' => 'drupal8',
        'password' => 'drupal8',
        'prefix' => '',
        'host' => 'database',
        'port' => '3306',
        'namespace' => 'Drupal\\mysql\\Driver\\Database\\mysql',
        'driver' => 'mysql',
        'autoload' => 'core/modules/mysql/src/Driver/Database/mysql/',
      );
      ```
- Do initial configurations and export (@see Initial configurations)

## Initial configurations

The above is all you need to do to have a working site with all the tooling for deployment.  2 commands, commit the code, and push.

However, when tasked to spin up a new site, you can do some initial configuration.  (Some of this may be automated in the future.)

### Theme Settings

Confirm that the following themes are installed and applied. Set the themes manually if needed.
- `/admin/appearance`
  - JCC Elevated - current default theme
  - JCC Storybook - component & pattern library for JCC Elevated
  - Gin - administration theme
  - JCC Components - alternate theme for trial court series until an upgrade to elevated

Configure theme at `/admin/appearance/settings/[jcc_elevated|jcc_components]`:
- Add the logo.svg to /web/sites/[site]/logo.svg
- Set the path to sites/[site]/logo.svg
- Set the various other theme settings. When in doubt, use the placeholders but not the `Base` scheme for jcc_components.
- Disable the translation menu if not in use

If admin console UI seems broken, confirm/configure administration theme at `/admin/appearance`:
- Set the Administration theme setting to not use default (currently Gin).
- Use administration theme when editing or creating content
- If admin console toolbar is black, uninstall Adminimal Admin Toolbar (adminimal_admin_toolbar) at `/admin/modules/uninstall`

### Create node 1 to use as front page:

Create Landing Page
- Title: Home
- Heading: Do Not Delete
- Lead: Do not delete this node, it is configured as the home page.  Edit this node to update the homepage.

### Configure site and modules

- Configure basic site information
  * `/admin/config/system/site-information`:
  * Site Name: `Superior Court of California | County of ...` (or California Courts | Site Name)
  * Site email: `no-reply@courtinfo.ca.gov`
  * Front page: `/node/1`  (or your home page if not node/1)
- Configure Hat & Shoe
  * Hat
    * `/admin/structure/menu/manage/hat`:
    * Supreme Court ... https://supreme.courts.ca.gov
    * Courts of Appeal ... https://www.courts.ca.gov/courtsofappeal.htm
    * Superior Courts ... https://www.courts.ca.gov/superiorcourts.htm
    * Judicial Council ... https://www.courts.ca.gov/policyadmin-jc.htm
  * Shoe
    * `/admin/structure/menu/manage/shoe`:
    * Privacy ... https://www.courts.ca.gov/11530.htm?rdeLocaleAttr=en
    * Terms of Use ... https://www.courts.ca.gov/11529.htm?rdeLocaleAttr=en
- Configure Bing Maps API Key:
  * `/admin/config/system/geocoder/geocoder-provider`
  * See JIRA for keys [ticket TCI-664](https://judasdg.atlassian.net/browse/TCI-664)
- Configure SendGrid (optional):
  * `/admin/config/system/keys/manage/sendgrid`
  * See JIRA for trial court keys [ticket TCI-664](https://judasdg.atlassian.net/browse/TCI-664)
  * For all other sites, a new key may be required from Azure Portal.
  * Note: site's email address needs to be `no-reply@courtinfo.ca.gov` for SendGrid to work.
- Setup defaults for Google Tag module:
  * `/admin/config/system/google-tag`
  * Add new container using existing or new key from Google.
- Configure GoogleRecaptcha (optional):
  * `/admin/config/people/captcha/recaptcha`
  * See JIRA for trial courts keys [ticket TCI-664](https://judasdg.atlassian.net/browse/TCI-664)
  * For all other sites, a new or different key may be used.
- Configure OpenID with Azure Active Directory (optional)
  - `/admin/config/services/openid-connect`
  - See OneNote documentation.
- Add language support as needed
  - `/admin/config/regional/language`
  - Add Spanish at minimum.

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

- Uninstall dev modules: `devel masquerade stage_file_proxy features_ui twig_xdebug`  @see `require-dev` in `composer.json`.
  - NOTES:  Initial deploy fails because of missing dev modules that are in the split configuration. This is due to db dump of local which has these modules enabled, trying to run on an env that doesn't have those modules.
  - `lando drush @local.[site] pmu devel masquerade stage_file_proxy features_ui twig_xdebug`

- Export config & setup .gitignore
  - `lando drush @local.[site] cex -y`
  - `lando config-ignore` or `scripts/sync_config_ignore.sh`  Make sure this is run before you commit exported config. It will ignore the files that are managed by jcc_tc2_all_immutable_config feature module.
  - NOTE: Some configurations are user managed but cannot be immutable or easily managed by the `config-ignore` script. These settings are still exported as source but ignored at `/admin/config/development/configuration/ignore`.
  - For elevated sites, modify loading weight in `core.extension.yml` to 100 if needed:
    - jcc_elevated_custom
    - jcc_elevated_embeds
    - jcc_elevated_sections (if installed)

- Export db from local
  - `lando drush @local.[site] sql-dump > data/[site]-install.sql`
  - Test DB export with `lando dbim -f data/[site]-install.sql -d [site]`. If `ERROR at line 1: Unknown command '\-'` occurs, eview line 1 and remove `\-` from comment before importing to Pantheon.

- Import the initial database dump to live environment to prepare pantheon for deployment.

- Commit changes and deploy to appropriate environment(s) for testing and other pre-launch work. Live (master), is where content creation will happen pre-launch.
  - Environments:
    - develop - deployment integration tests
    - epic-uat - stakeholder/user testing and review
    - stage - pre-production/final tests
    - live - production
- **Enabling Solr and Redis Pantheon Services:** To properly setup Solr and Redis from Pantheon's dashboard, the master branch at Pantheon for the site that we deploy into must be populated with our `pantheon.yml` configurations.
  - If Solr and Redis are enabled before our code is deployed, disable the services and, after our code has been deployed to at least `develop`, re-enable them.
  - If our code has been deployed, try disabling and re-enabling the services.
  - If setup is still not correct (e.g. Solr version incorrect) especially if the site is already in production, disable the service and modify `pantheon.yml` to apply the correct settings. Because of our multisite deployment process, modify `pantheon.yml` directly from the Pantheon master branch. For example:
    - `git clone ssh://...` which is available from `Connection Info` view for the site.
    - Make a temporary change to `pantheon.yml` and push to master.
    - Revert the change as an edit and commit to master to avoid conflicts with our deployments.
