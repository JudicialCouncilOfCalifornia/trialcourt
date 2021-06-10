# Local Development

* Docker: https://www.docker.com/community-edition
* Lando: https://docs.devwithlando.io

Lando/Docker optional, any `*AMP` environment will do, but there is helpful tooling here for Lando.

Once installed, `cd` to project directory and type `lando` for a list of commands.

## Acquire a database dump.

Database can be fetched in multiple ways. I recommend saving to `data/` directory which is ignored and within the environment so it can be saved for future resets and imported from there, without cluttering up the project root with different versions of the database.

There's lando tooling for this:

`lando dbget @[site].live` - will dump the database from the indicated alias to your data directory.

It first checks for an existing one of the same name and prompts to replace or stop. Then runs a drush command you could run manually if you prefer.

### Other options. Options:

 - Drush Alias
   - `lando drush @[alias] sql-dump --gzip > data/[alias]-YYYY-MM-DD.sql.gz`
   - `lando sql-sync @[site].live @local.[site]` to directly sync a live db to local site.
 - Terminus
   - `terminus backup:create [project].[env] --element=db`
   - `terminus backup:get [project].[env] --element=db`
 - Pantheon Dashboard
   - Select the environment tab or multisite
   - Select the Backups vertical tab
   - Download the latest database backup, or create a new one.

You'll need access to the project. If not you'll need to ask for a database dump.

## Spin up the local.

 `git checkout master` - Start with a clean master to build your local.
 `lando start` - Spin up the environment.

Then get to a fresh state.
 - `lando dbim [path to db] -d [site]` - Import your database.
 - `lando composer install` - Composer install.
 - `lando reset` - Runs updb, cim, cr ...

Composer install will move `settings.local.php` and `services.local.yml` to the `sites/*/` directories with functional configuration.  You can replace or alter these files if you wish. They will not be replaced if they exist.

### Shortcut

There's lando tooling that will do the above for you with one command.

- `lando fresh -l [site].lndo.site -f data/[database].sql.gz`
- `lando fresh -h` for help.

Make a new feature branch with your ticket id:

`git checkout -b feature/TC-[id]--short-description`

**Ready to work.**
