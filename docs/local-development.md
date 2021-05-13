# Local Development

* Docker: https://www.docker.com/community-edition
* Lando: https://docs.devwithlando.io

Lando/Docker optional, any `*AMP` environment will do, but there is helpful tooling here for Lando.

**WARNING: Docker for Mac 2.2 currently breaks Lando. Use the Lando installer to install it's preferred version of Docker.**

Once installed, `cd` to project directory and type `lando` for a list of commands.

## Acquire a database dump.

Database can be fetched in multiple ways. I recommend saving to `data/` directory which is ignored and within the environment so it can be saved for future resets and imported from there, whithout cluttering up the project root with different versions of the database.

There's lando tooling for this:

`lando dbget @[site].live` - will dump the database from the indicated alias to your data directory.

It first checks for an existing one of the same name and prompts to replace or stop. Then runs a drush command you could run manually if you prefer.

### Other options. Options:

[env] = live or dev (not develop), depending on which you're treating as master. (Post or Pre launch).

 - Drush Alias
   - `lando drush @[alias] sql-dump --gzip > data/[alias]-YYYY-MM-DD.sql.gz`
 - Terminus
   - `terminus backup:create [project].[env] --element=db`
   - `terminus backup:get [project].[env] --element=db`
 - Pantheon Dashboard
   - Select the environment tab or multisite
   - Select the Backups vertical tab
   - Download the latest database backup

You'll need access to the project. If not you'll need to ask for a database dump.

## Spin up the local.

 `lando start` - Spin up the environment.
 `git checkout master` - Start with a clean master to build your local.

Then get to a fresh state.
 - `lando db-import [path to db]` - Import your database. Store db in `data/`.
 - `lando composer install` - Composer install.
 - `lando th` - "Theme: Help" for detailed info on managing multiple themes.
 - `lando reset` - Runs updb, cim, cr ...

Composer install will move `settings.local.php` and `services.local.yml` to the `sites/*/` directories with functional configuration.  You can replace or alter these files if you wish. They will not be replaced if they exist.

There's lando tooling that will do the above for you with one command.

- `lando fresh -l [proxy].lndo.site -f data/[database].sql.gz`
- `lando fresh -h` for help.

Make a new feature branch with your ticket id:

`git checkout -b feature/TC-[id]--short-description`

**Ready to work.**

