# Code Management

From the project root:

## Adding Contrib Modules

 - `lando composer require drupal/[package_name]` to add it to the composer.json without updating everything.
 - `lando composer update drupal/[package_name]` to update only the desired module.

## Updating Core

- `lando composer update drupal/core-recommended --with-dependencies`

Sometimes composer will fail with a dependency issue. You can usually just add the dependency to the update list and try again. This list can get long if things are really out of date.

i.e. `lando composer update drupal/core-recommended drupal/[dependency 1] symfony/[dependency 2] ... --with-dependencies`

## Updating Contrib Modules

 - `lando composer update drupal/[package_name] --with-dependencies`

Sometimes several contrib modules are several versions behind.

**Do not use `lando composer update` without specifying a module, or it will update everything that's outdated at once, possibly introducing regressions which you'll have to do much more testing for.**

*Updates should be controlled and tested well. It's easiest to do that in smaller chunks. Especially watch out for BETA, ALPHA, or DEV versions of modules which are not stable and make no guarantees about not breaking things between updates. Ideally, never use Alpha/Dev modules and use BETA's sparingly. Consider contributing to the project to help get it to a full release.*

## Removing Contrib Modules

Enabled modules should be removed from a code base in 2 separate releases. The first release update should simply uninstall the module. The second release should remove the module from the codebase as described below. If you do it all at once Drupal will not be able to find the module code on a test or production environment to be able to uninstall it, because it won't exist anymore.

Phase 1: Uninstall the module:

 - `lando drush @[alias] pmu [module]` - uninstall the module.
 - `lando drush cex` - export the config changes caused by uninstalling the module.
 - Deploy the changes to update the Production site.

Phase 2: Remove the module:

 - `lando composer remove [package]` will remove a package from require or require-dev, without running all updates.

## Applying Patches

If you need to apply patches, you can do so with the
[composer-patches](https://github.com/cweagans/composer-patches) plugin.

To add a patch to drupal module `foobar` insert the patches section in the extra section of composer.json:

```json
"extra": {
    "patches": {
        "drupal/foobar": {
            "Patch description": "Drupal URL to patch"
        }
    }
}
```
