# Feature and Config Management

Meta:

 - This takes attention to detail.
 - There are a lot of moving parts.
 - I've tried to make the steps clear and scalable for dozens of sites.
 - This would be necessary regardless of hosting platform, (not a because we're running multisite on pantheon issue, it's because we want to keep the fleet in sync with all updates)
 - There are some Drupal quirks with config diff sort.

**UPDATE 2021-12-17**

While the above is still true, we've refined the process to be more efficient. As we grew to more sites, the time it took to sync features and then export on every site before deployment caused deployments to take several hours.

Now, rather than syncing config via feature import > config export, we rely on the Features to manage all it's own config and remove the redundancy of Drupal config management. We DO still use Drupal config management for anything not managed by the feature module jcc_tc2_all_immutable_config. The feature config is ignored by Drupal core and those exported config files are ignored in git.

This is handled by a hook and a bash script to run instead of "fleet config-sync". You only need to work with the pilot site "Inyo" now. You do not need to install all sites to sync config.

See below for more info.

---

There is one feature module on this profile. JCC Components All Immutable Config
As it's name suggests, this is for config that is unchanging and enforced across all sites.  Almost all config is captured in this feature. That which is not is considered site by site and should be managed as normal config.

EXCEPTION: Installing new modules. See the end of this section for details.

When config changes are made on the primary site (inyo) the steps to sync are:

  - Clone repo
  - `git checkout master`
  - `lando start`
  - `lando composer install`
  - `lando drush sql-sync @inyo.live @local.inyo` - sync the db from live to local.
  - `lando reset -l inyo.lndo.site` - (updb, cim, cr - should have no updates and nothing to import)
  - `git checkout (new feature)` - The one we want to sync to fleet.
    - Alternatively, do this on a release branch after you've merged any features to be deployed. Sync config for the release and deploy the release branch.
  - `lando reset -l inyo.lndo.site` - reset inyo for the feature or release branch.
  - Verify the feature installs and works as expected on local inyo before syncing config. If not, send it back with notes.
  - Does anything need an update hook? i.e. fields deleted, field types changed, db schema changes, **new modules enabled**?
    - Yes: Write update hooks in `jcc_tc2_all_immutable_config.install` to do that work before config can import. **Include hooks to install new modules but we also need to copy config to all relevant core.extension.yml files. More info below.**
  - Update jcc_tc2_all_immutable_config feature module to collect all the relevant config from our new feature `admin/config/development/features`.
    - Add any new config that should be added, and don't add any that should be unique to every site.
    - Do NOT omit config arbitrarily. Degradation of the system makes it harder and harder to manage over time. Every decision should be made in favor of maintaining integrity.
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
    - Write the new feature config which will update `jcc_tc2_all_immutable_config/config/install/`.
      - A hook in the Module adds any config in the above directory to the ignore list so it's fully handled by Features and not Drupal core.
  - Add any newly enabled modules to the module list in `jcc_components_profile.info.yml` so they're enabled for new sites.
    - **We also need to copy newly enabled modules to the **core.extension.yml files of each site on the profile.**
    - Ideally this would be all the sites we manage, but there are some exceptions. (deprep, md (should be removed), newsroom, supremecourt, webservices).  Exceptions will NOT have a `.gitignore` file in the `config-[site]` directory.
    - You can sync this with the old `scripts/fleet config-sync` but that would take several hours.
    - A shortcut would be to just add the new line(s) to the files manually. 40ish direct file edits is much faster if you use search and replace in your editor to do them all at once.
    - See below for an example.
  - Watch for errors and troubleshoot as needed.
    - Run `lando config-ignore` or `scripts/sync_config_ignore.sh`. This runs the following:
      - Add files from `jcc_tc2_all_immutable_config/config/install/` to `config/config-default/.gitignore` because Drupal still exports them, even though it ignores them on import.
      - Copy Default's .gitignore to all other config directories that have a `core.extension.yml` that enables `jcc_tc2_all_immutable_config`. In other words, only the sites that use this universal config.
      - Remove .gitignore created in config-inyo/ to provide a normal developer experience, because Inyo is the prototype site.
  - Visually verify all the new config files in `jcc_tc2_allimmutable_config/config/install` match what you expect from the config changes in the feature or release branch.
  - I do this by doing git add on the profile which shows me which config I'm looking for.
  - It's probably a good idea to write tests to test the new feature as well as a small suite of tests that run on every deploy to watch key pages/user flows for breakage. @see [Automated Testing](./automated-testing.md)
  - If it looks good, commit and push to github and PR to `develop`.
  - Make sure it deploys right to develop and QA test some other sites to make sure things work the way you expect.
  - If it looks good on `develop`, PR/merge the _**FEATURE or RELEASE**_ branch to `stage`
  - update the ticket with notes for how to test so other stakeholders can review and approve for production.
  - If it's approved for production, plan your deployment to production.
    - hotfix style: the feature can be pr/merged to master directly on it's own
    - group release: the feature branch can be merged to a release branch (cut from master), with a collection of other approved features to be deployed together

## Enabling New Modules Example

As described above, with the way we're managing config now, we still need to add newly enabled modules to every relevant `core.extension.yml` file.

Let's say we want to enable honeypot. This would be a new line in the `config/config-inyo/core.extension.yml` file. You can find these with a `git diff master -- config/config-inyo/core.extension.yml`

We can use search and replace in our code editor to do this quickly.

Note the line that comes before `honeypot: 0` in the inyo config. In this case it's `google_tag: 0`.

**In the search parameters:**

 - Files to include: `config/*/core.extension.yml`
 - Search: `google_tag: 0`
 - Replace:
```
google_tag: 0
  honeypot: 0
```
We're replacing the known previous line with the same line, plus the next one we want to add.  (Make sure you get the spacing right.)

In VS Code you will get a list of results where you can select each one and choose to do the replace or not. I'm guessing PHP Storm and others have similar functionality. Otherwise you can do a global search and replace and then undo the few exceptions that are not part of the immutable install profile. (see above).

With the install hook and the `core.extension.yml` update we can enable the module so dependent config won't error and modules won't just get disabled again on config import.

On the whole, this all takes a fraction of the time the old config-sync would take for 40+ sites.

## Unstick Features

Sometimes features:sync doesn't effectively update all config across all projects.

Sometimes config is the same but the line order is different between feature and config.  This will result in feature always indicating "changed", but never actually importing the difference. This should be fixed manually. Edit the affected config files for the site in question, to match the feature's version of the same file.  This takes some time and is tedious, but we have to have clean config for syncing to be clear and effective, so we know when it's safe to deploy code. Discrepancies like this make this difficult, so clean as we go.

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
  - Is this config or module different for every site?
    - (Do NOT add to jcc_components_all_immutable_config feature.)
  - Is this config or module specifically for development?
    - (Add to develop config split.)
  - Is this config something that should not be stored in the repo? i.e. API Keys
    - (Add this to config ignore as well as `config/.gitignore`)
  - Is this config that we treat like content that should not be managed as config? i.e. webforms
    - (Add this to config ignore as well as `config/.gitignore`)
