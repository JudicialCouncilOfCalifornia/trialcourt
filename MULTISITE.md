# MULTISITE CHANGELOG

## Codebase Changes

In preparation for multisite, `sites/default` will become a base install. We can use this for reviewing and updating the base installation. `tc.lndo.site`

Slo will live in `sites/slo`, and be accessed (with lando) at `tc-slo.lndo.site`.

The config directories will become `config/config-default` and `config/config-slo` respectively.

If you have a `config/config-local` you should rename that to `config/config-default-local`

The pattern will be `sites/[sitecode]` and `config/config-[sitecode]` where `[sitecode]` is a short abbreviation for the court. i.e. `slo`

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
   - String change: `sites/[new]/settings.local.yml` database settings:
     - `'host' => 'database'`
     - > `'host' => '[new]db'`,
   - String change: `sites/[new]/settings.pantheon.php` config dir settings:
     - `CONFIG_SYNC_DIRECTORY => '../config/config-default'`,
     - > `CONFIG_SYNC_DIRECTORY => '../config/config-[new]'`,
   - String change: `sites/[new]/settings.php` config dir settings:
     - `$config_directories['sync'] = '../config/config-default';`
     - > `$config_directories['sync'] = '../config/config-[new]';`
     - `$config['config_split.config_split.local']['folder'] = '../config/config-default-local';`
     - > `$config['config_split.config_split.local']['folder'] = '../config/config-[new]-local';`

   - `drush si -l tc-${NEW}.lndo.site -vvv --site-name="SITE NAME" --account-mail="jcc@example.com" --account-name="JCC" --account-mail="jcc@example.com`

   - POST INSTALL
     - String change: `config/config-[new]/config_split.config_split.local.yml`:
       - `folder: ../config/config-default-local`
       - > `folder: ../config/config-[new]-local`
