# Feature and Config Management

Meta:

 - This takes attention to detail.
 - There are a lot of moving parts.
 - I've tried to make the steps clear and scalable for dozens of sites.
 - This would be necessary regardless of hosting platform, (not a because we're running multisite on pantheon issue it's because we want to keep the fleet in sync with all updates)
 - There are some drupal quirks with config diff sort.

---

There is one feature module on this profile. JCC Components All Immutable Config
As it's name suggests, this is for config that is unchanging and enforced across all sites.  Almost all config is captured in this feature. That which is not is considered site by site and should be managed as normal config.

When config changes are made on the primary site (inyo) the steps to sync are:

  - `git checkout [feature branch]`
  - `git pull [feature branch]` to make sure it's up to date.
    - `git pull origin master --rebase` to also make sure it has the latest master code.
  - `lando composer install`

  - `scripts/fleet db-pull live` to get every site's database installed.
    - Watch for any failures. If there are any, not which site and update individual sites with `lando drush sql-sync @[site].live @local.[site]`
  - `scripts/fleet reset local -y` to get local in a reset state.
    - I'll run this twice to catch dependencies.
    - Watch for errors and troubleshoot as necesary

  - export primary site config as normal with cex  (Already done if you're pulling a PR branch, but do it anyway after RESET.)
  - Use features UI to update the Immutable Config feature
    - Add any new config that should be added, and don't add any that should be unique to every site.
    - Do NOT ignore config arbitrarily. Degradation of the system makes it harder and harder to manage over time. Every decision should be made in favor of maintaining integrity.
    - As a rule of thumb, the ideal type of config to be left out is one that is always different on every site.
    - Everything else should be Immutable and standard across the "fleet".
    - Omit (not a cmplete list):
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
  - The updb command runs at the start of a config sync so, Write update hooks in jcc_tc_all_immutable_config for:
    - enabling new modules
    - disabling modules to be removed
    - deleting field instances and storage.
  - reset all sites
    - checkout master
    - pull live databases
    - clean build (composer install)
    - updb, cim, cr
  - export config from all sites' current fresh live state
    - this should be clean but just to ensure config matches live state
    - if it's not clean, troubleshoot whether to commit the changes or clear them.
    - scripts/fleet reset local should be clean before syncing config.
  - if new modules were enabled:
    - make sure you have an update hook to enable them in `jcc_tc_all_immutable_config.install`
    - Add the installed modules to `jcc_tc_all_immutable_config.info.yml`
  - if modules are uninstalled:
    - make sure you have an update hook to enable them in `jcc_tc_all_immutable_config.install`
    - Remove the uninstalled modules from `jcc_tc_all_immutable_config.info.yml`
  - run `scripts/fleet config-sync`
    - confirm exported config matches the main site and exported feature changes.
  - reset all sites to ensure config changes import correctly
    - `scripts/fleet reset local`
  - git add config changes but do not commit yet
  - export config from all sites again
    - use git status and git diff to confirm integrity
    - troubleshoot as necessary
  - commit changes

## Unstick Features

Sometimes features:sync doesn't effectively update all config across all projects.

Sometimes config is the same but the line order is different between feature and config.  This will result in feature always indicating "changed", but never actually importing the difference. This should be fixed manually. Edit the affected config files for the site in question, to match the feature's version of the same file.  This takes some time and is tedious, but we have to have clean config for syncing to be clear and effective, so we know when it's save to deploy code. Discrpencies like this make this difficult, so clean as we go.

Examples:
 - If a field is removed form an entity type, for example, the exported config from the primary site will not import cleanly into the fleet because the field needs to be removed in all sites first.
 - For an unknown reason updating a filter format to include media as a dependency failed to be imported fully to some sites in the fleet.

Run `lando feature:sync until all the fleet sites show no feature imports. If multiple runs of feature sync keep showing the same feature imports on the same sites, manual intervention is required.

In cases like the removal of a field form an entity type, the best practice would be to include an update hook that deletes the same config on updb, before the feature sync is done. The feature:sync command now includes an updb before the feature revert, to make sure hooks that remove config are done first.

Then remove the deleted config from the `jcc_tc2_all_immutable_config/config/install` directory. Then run `feature:sync`.

NOTE: Create a "fleet" command that allows us to run batch commands across the whole fleet to facilitate things like this.  ie. `fleet updb` `fleet cim` ...

In more mysterious cases the only course may be to
  - identify the faulty config from the feature:sync run,
  - copy the file from the primary site (inyo) and overwrite the affected site's config with that file.
  - Be sure to strip the uuids and save
  - drush cim the updated config into the affected site.
  - drush cex the config to get updated uuids
  - run feature:sync again to ensure success
  - repeat as needed for affected sites

---
Config Split and Features

Live split should contain much of the config that's not included in features.
core.extension should not be included in features nor live config. it can stay in the main config for the site and is augmented by the config split settings.

Questions to ask:
  - Is this config/module different for every site? (Do NOT add to jcc_components_all_immutable_config feature.)
  - Is this config/module specifically for live?  (Add to Live config split.)
  - Is this config/module specifically for development? (Add to develop config split.)


Is a Custom Upstream a better way to manage config across all these sites? Every site uses config-default and we hardcode per-site variance in settings.php?

Are we stuck on Pantheon forever?

Can we look at Aegir for tricks and concepts? Or move to a platform where we can use it?

Can we set up an Aegir platform and manage pantheon sites? Spin one up locally and see.

FLEET STATS

---

