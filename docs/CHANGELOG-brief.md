# Change Log Affecting Developer Workflow

These are some brief notes about how development of the four legacy sites (md, webservices, newsroom, deprep), may be affected by the changes introduced by the tooling changes for v2.

## Lando

In an effort to speed up and simplify local development I've altered the .lando.yml configuration in a way that will require a rebuild and some changes to your settings.local.php files for each multisite.

### Proxy

We no longer need to add a proxy url for every multisite. Instead we'll use a wildcard.

```
proxy:
  appserver:
    - "*.lndo.site"
```

We will no longer need to alter the .lando.yml file for each new site, but there's one thing to note.  Because of the * subdomain, you won't be able to access any other lando instances while this one is running.  If you need to switch to another project, remember to run `lando stop` on this one first.

### Databases

Previously we required a separate database container for each multisite. This was very slow and cumbersome for local development and required manual configuration. Now there's a script that creates a new database in the single `database` service.  `/app/scripts/dbcreate.sh`

This script is run on a lando build and also during a new site installation with `lando multisite`.

The script scans the site's directory and makes a list of directory (site) names, then generates a database for each one.

Your `settings.local.php` files for each site will need to have the `database` and `host` values updated:

`[site]` = the name of the site directory i.e. colusa

```
/**
 * Database settings: *
 */
$databases['default']['default'] = array (
  'database' => '[site]',  // <-- previously "drupal8"
  'username' => 'drupal8',
  'password' => 'drupal8',
  'prefix' => '',
  'host' => 'database',  // <-- previously "[site]db"
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);
```

### Tooling and Commands

#### lando dbim

Due to the database change, we can't use the built in `lando db-import` command because it can't specify a database in the container. It expects one database in the service.

So, I've added a command in `lando.yml` that serves the same purpose but includes an option to specify the database.

 - `lando dbim [file] -d [database]`
 - i.e.: `lando dbim data/colusa.sql.gz -d colusa`

---

Other commands may have been modified a little but should still work essentially the same way.


### Features

The new sites take a different approach to Feature management. Rather than multiple feature modules for discrete, modularized features, there's one feature module that contains only the configuration that should be enforced across all sites. See the Features documentation for detailed info.

The tooling for feature sync to individual environments is removed and new "fleet" wide tooling has been created to support keeping multiple sites in sync. More on "fleet" elsewhere but it allows for using "bundle" to indicate wether to update the new sites or the legacy site features.  Default bundle is `jcc_tc2`.  The legacy bundle is `jcc_tc`. When configuration is synced across the sites by bundle, only the sites affected by that bundle will be affected. So we can update v2 and legacy sites independently via the same mechanism.

If there is still a need to target specific legacy sites' features, you can use `drush` commands directly.  `lando drush | grep feature` for options.

