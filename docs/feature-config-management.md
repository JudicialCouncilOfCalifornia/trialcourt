# Feature and Config Management

Meta:

 - This takes attention to detail.
 - There are a lot of moving parts.
 - I've tried to make the steps clear and scalable for dozens of sites.
 - This would be necessary regardless of hosting platform, (not a because we're running multisite on pantheon issue, it's because we want to keep the fleet in sync with all updates)
 - There are some Drupal quirks with config diff sort.

---

There is one feature module on this profile. JCC Components All Immutable Config
As it's name suggests, this is for config that is unchanging and enforced across all sites.  Almost all config is captured in this feature. That which is not is considered site by site and should be managed as normal config.

When config changes are made on the primary site (inyo) the steps to sync are:

  - Clone repo
  - `git checkout master`
  - `lando start`
  - `lando composer install`
  - `scripts/fleet db-pull live` - (install all live databases to local)
  - `scripts/fleet reset local -y` - (updb, cim, cr - should have no updates and nothing to import)
  - `git checkout (new feature)` - The one we want to sync to fleet
  - `lando reset -l inyo.lndo.site` - reset inyo for the feature branch where the initial work was done.
  - Verify the feature installs and works as expected on local inyo before syncing config. If not, send it back with notes.
  - Does anything need an update hook? i.e. fields deleted, field types changed, db schema changes, new modules enabled?
  - Yes: Write update hooks in `jcc_tc2_all_immutable_config.install` to do that work before config can import.
  - Update jcc_tc2_all_immutable_config feature module to collect all the relevant config from our new feature `admin/config/development/features`.
    - Add any new config that should be added, and don't add any that should be unique to every site.
    - Do NOT ignore config arbitrarily. Degradation of the system makes it harder and harder to manage over time. Every decision should be made in favor of maintaining integrity.
    - the ideal type of config to be left out is one that is always different on every site.
    - Everything else should be Immutable and standard across the "fleet".
    - Omit (not a complete list):
      - Simple Configuration:
        - contact.settings
        - core.extension
        - jcc_components.settings
        - system.site
      - Contact Form:
        - All
      - Features Bundle:
        - All EXCEPT: jcc_tc2
      - Geocoder Provider:
        - All
      - Key:
        - All
  - Add any newly enabled modules to the module list in `jcc_components_profile.info.yml` so they're enabled for new sites.
  - `scripts/fleet config-sync` this runs the following on ALL sites.
    - Watch for errors and troubleshoot as needed.
    - `drush cr` cuz drupal
    - `drush updb` to run any update hooks needed to enable modules, delete
    - `drush fra` feature import on all enabled feature modules in bundle jcc_tc2 on all sites. (all immutable config is the only one)
    - `drush cex` export new config changes added by the feature import so each site config is up to date.
  - `scripts/fleet reset local -y` reset the fleet again. It should show no updates, nothing to import.
  - Watch for issues and troubleshoot as needed.
  - Visually verify all the new config files.
  - I do this by doing git add on the profile which shows me which config I'm looking for.
  - Then scanning through the long list of files for that "pattern" of files.
  - It's probably a good idea to write tests to test the new feature as well as a small suite of tests that run on every deploy to watch key pages/user flows for breakage. @see [Automated Testing](./automated-testing.md)
  - If it looks good, commit and push to github and PR to `develop`.
  - Make sure it deploys right to develop and QA test some other sites to make sure things work the way you expect.
  - If it looks good on `develop`, PR/merge the _**FEATURE**_ branch to `stage`
  - update the ticket with notes for how to test so other stakeholders can review and approve for production.
  - If it's approved for production, plan your deployment to production.
    - hotfix style: the feature can be pr/merged to master directly on it's own
    - group release: the feature branch can be merged to a release branch (cut from master), with a collection of other approved features to be deployed together


## Unstick Features

Sometimes features:sync doesn't effectively update all config across all projects.

Sometimes config is the same but the line order is different between feature and config.  This will result in feature always indicating "changed", but never actually importing the difference. This should be fixed manually. Edit the affected config files for the site in question, to match the feature's version of the same file.  This takes some time and is tedious, but we have to have clean config for syncing to be clear and effective, so we know when it's save to deploy code. Discrepancies like this make this difficult, so clean as we go.

Run `scripts/fleet config-sync` until all the fleet sites show no feature imports. If multiple runs of feature sync keep showing the same feature imports on the same sites, manual intervention is required.

In more mysterious cases the only course may be to
  - identify the faulty config from the config-sync ,
  - copy the file from the primary site (inyo) and overwrite the affected site's config with that file.
  - Be sure to strip the uuids and save
  - drush cim the updated config into the affected site.
  - drush cex the config to get updated uuids
  - run config-sync again to ensure success
  - repeat as needed for affected sites


## Config Split, Ignore, and Features

Questions to ask:
  - Is this config/module different for every site?
    - (Do NOT add to jcc_components_all_immutable_config feature.)
  - Is this config/module specifically for development?
    - (Add to develop config split.)
  - Is this config something that should not be stored in the repo? i.e. API Keys
    - (Add this to config ignore)
  - Is this config that we treat like content that should not be managed as config? i.e. webforms
    - (Add this to config ignore)
