# Managing Features

This project uses Features to share configuration across multisites and with the installation profile.

Handling this effectively requires careful constuction of Feature modules.

## Some Rules to Make Features Easier to Deal With

### Single function modules

For example, a feature module should only provide one logical set of
configuration. i.e. A single content type, or a single paragraph type.

### Be careful with dependencies

By default, Features tries to automatically include a buch of configuration
that depends on, or is depended on by, the feature module itself. This has been
disabled in favor of crafting clean and discrete features. This prevents
"dependency hell" when you want to change, remove or disable some modules or
configuration. Make your dependencies specific to the function of your feature
module. That is the modular way after all.

Where you can, create separate features for configuration other features
may depend upon. A good example of this is field storage.

In Drupal we have fieldable entities. Field configuration consists of two
separate parts per field. "Field Storage" and the "Field Instance", each of
which have their own configuration file.

Field Storage is the base field configuration and can be reused by multiple
entities of it's type.  i.e. `field.storage.node.field_title`

This example defines a `field_title`, that's available for `node` entities. So
you can add `field_title` to any content type, reusing this base field storage
configuration.

Adding the field to a content type creates a second config file like
`field.field.node.page.field_title` which defines the specific configuration
for that field on the page content type. Other entitiy types such as
Paragraphs, or Media get their own base "Field Storage" configuration, so you
can have `field.storage.paragraph.field_title` which is totally separate from
the field storage config for node.

Since it is possible to share field storage config across multiple entity
bundles (i.e. content types for node), it does not make sense to include the
storage config in a feature for a particular content type. A Page feature
could use it, AND an Event feature could use it, etc.

So it's better to create a Feature that simply contains all the Field Storage
config, then any other feature can include it as a dependency to get access to
any field storage config available.

If a field storage config ended up in content type feature, and that feature
was disabled or removed from a particular site, the other content types that
depend on it would be missing the dependency.

That's one example of how you can run into trouble if you're not careful in
defining your feature modules.

Always watch for opportunites to move configuration dependencies to a logical
base module that other features can depend on and keep your modules minimal
and clean.


## EXAMPLE: Create a Content Type Feature

Create your content type as you normally would in your base Drupal project, (the source of truth for distributed features).

Once this is complete with fields and display settings, do a regular config export to capture the new config in the site's config directory.

Run `git status` and, (assuming your branch was clean to start with), you should see the list of new configuration files for the content type. You want to capture the same configuration in a feature.

Navigate to the Features UI at `admin/config/development/features` and create a new feature named after the content type.  i.e. Announcement. Include a description so it's easy to understand the purpose of this Feature. i.e. Announcement content type.

Make sure you're working in the "JCC TC" bundle and the path is set to `profiles/jcc_tc/modules/custom`.

Mark all config as required. This just sets the config in this module to required on install/import.

Allow conflicts should be off, but you can check it to help troubleshoot
issues with dependencies across features. Config with conflicts appears in red
on the components section to the right. You do not want to have features with
conflicts. If you do, decide where the conflicting config truly belongs and
factor it out, perhaps into a more base feature like the "Field Storage"
example above.

On the right, is the "Components" section where you can select the config for
this module. Often you can just use the search field to filter to the config
you want. I.e. "announcement" will give you everything you need for this
example. Generally, check the related items in the following sections:

 - Base field override - node.announcement.promote. These are base fields
 built in to every Drupal node. Promoted, published, title label, etc., which
 can be overridden per content type.
 - Block - For views blocks for this content type, though you might want a
 separate feature for views and or blocks. Watch out for dependency issues and
 note that block placement is specific to a particular theme so capture custom block definitions, but not block placement config.
 - Content language settings - i.e. node.announcement
 - Content Type - Announcement
 - Entity Form Display - node.announcement.default
 - Entity View Display - node.announcement.default, node.anouncement.teaser
 - Field - node.announcement.body, node.announcement.field_alert_type ...
 - Tour - announcement - While this is not part of the node type specifically,
 it's specific enough that it's probably ok to include with the content type.
 - View - announcements - Like Blocks, it might be better to make views thier
 own features, depending on the complexity of the view and what it interacts
 with. The feature module for the view could add this content type feature
 module as a dependency.

 You should recognize these items from the list you just exported to your
 standard config directory. You can even use the search field to find those
 specific config items.

 Deselect any other config that may have been auto selected but is not on the
 list you got from the config export. This may be shared or related
 configuration that is not specific to your content type. Consider what module
 it might fit in and if a new module is needed for it, or if it's not needed
 in a feature module at all.

 Click the "Write" button to write the new module to the `modules/custom`
 directory in the JCC TC profile.

 Run `git status` again and compare the config from your initial export to the
 config written in the new or altered feature module(s). They should match and/
 or you should not see any config that doesn't belong.

 ## Roll Features Out to Multisites

 So far, we've:
  - made changes to the site that represents our source of truth
  - we've exported that site's config as normal with `drush cex`
  - we've captured that same config in a feature module or modules

Now you need to roll it out to the other multisites.

All the features modules in JCC TC profile are available to sites installed
with that profile.

### Enabling a new Feature across all sites.

- You could use drush and enable it for each local alias. `lando drush @alias en jcc_tc_announcements`
- Or write an update hook in a module all sites are certian to have enabled. `jcc_tc_custom` for example.
- Or enable them through the UI on each local site.

There is lando tooling `lando feature:enable [module_name]` that will enable a
module on all multisites and then export their config with standard config
management.

The important part is, that once the Feature's config is installed into the
site, the site must export it and manage it as standard config with `drush cex`.

Standard config management is how configuration is deployed across environments.
Feature config managment is simply for duplicating config across different sites and is done in a local development environment.

### Updating an existing feature module.

If you make a change to configuation on the base site you will see that
Features will be marked as changed in the Features UI. You should review the
feature changes and then write the feature as described in Creating a Feature,
above.

Remember that the config changes on the base site should be exported with
`drush cex`, and you can use that list of changed/new files to guide the update
of features.

Once you're satisfied that the Feature is correct, you can sync the changes
across multisites with `lando feature:sync`. This will re-import the Feature
module's config with the new changes and then export the standard config
changes for you.

`lando feature:sync` loops over each local site alias and runs:
 - `drush $SITE fra -y --bundle=jcc_tc` - to import feature changes.
 - `drush $SITE cex -y`- to export config changes.

 Run `lando drush | grep features` for more info about features drush commands.

### Destructive Behavior and Modules

By design, Features does not do anything destructive with config. You can't
delete fields with it, for example. If you modify a feature to remove a field
from a content type, it will only affect new installations of that feature.
Any sites with the field installed, will keep the old field, even after feature
sync.

This is usually desired behavior, as fields contain content, and deleting
fields permanently deletes any content that lived in them. You don't want to
do that across sites, unless you're really sure.

If you are really sure, you can write an update hook in the feature module to
do the work. This is beyond the scope of this documentation but Feature modules
are just modules. You can create an `.install` file for installation processes,
update hooks, etc., or `.module` file for preprocess hooks, or anything you
want.  Of course, keep it specific to a single purpose module.

See additional documentaion on https://drupal.org about module building, or
google `drupal 8 programmatically delete a field`. <-- but probably don't.

## Finishing Up

Once all the config is exported with Drupal config management you can commit
the changes to the repo and deploy it as usual.

Recap:

 - Create or modify base site config
 - Export with config management `drush cex`
 - Use exported changes to inform the creation/modification of Feature module.
 - Write the feature module to the profile to make available to multisites.
 - Import config from Features to multisites by enabling or syncing, manually or with tooling.
 - Export changes with config managemnt for each multisite, manually or with tooling.

## Divergence

Beyond the complexity and additional considerations for managing configuration accross multiple sites, the biggest risk is divergence of a site from the standard.

### Scenarios:

#### Universal Features With Immutable Values

This is the ideal. These features are the same across all sites in the installation. Configuration is managed as described above. Config changes are captured in discrete features to install on all sites. Each site then uses configuration management to export the new config to it's own config directory for deployment to other environments.

 - `lando feature:sync` - Sync latest feature updates to all multisites.
 - `lando feature:enable [module]` - Enable a feature (or any) module AND export the new config changes, on all multisites.

#### Site Specific Configurations

There may be a considerable amount of configuration that is just not captured in a feature module. This will be specific to the each site and managed with the standard config management process.

#### Universal Config With Mutable Values

Feature modules can be enabled or disabled on a per site basis. If a site plans to remove a feature, or alter configuration provided by a base feature in a way that will not be rolled out to all other sites, that feature module should be disabled for that site.

In doing this, you preserve the ability to run a scripted features import across all sites. The disabled feature modules on specific sites will not be re-imported for those sites. Any site specific configuration that diverges from that module will not be affected and will be managed with the site specific standard config management process.

*Note: See README.md for notes on standard config management and enabling/disabling modules.*
