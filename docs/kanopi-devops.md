# Kanopi DevOps

DevOps and local-development guide for the **Judicial Council of California — Trial Court** platform (`trialcourt`).

This is a **Drupal 9 multisite** that powers ~70 California court websites from a single codebase, hosted on **Pantheon** (one Pantheon site per court), with **DDEV** for local development and **CircleCI** for build/test/deploy.

**Local Development:** This project uses **DDEV**.

## Important links

* **GitHub:** [JudicialCouncilOfCalifornia/trialcourt](https://github.com/JudicialCouncilOfCalifornia/trialcourt)
* **CircleCI:** [pipelines for this repo](https://app.circleci.com/pipelines/github/JudicialCouncilOfCalifornia/trialcourt)
* **Pantheon:** each court is a separate Pantheon site named `jcc-<site>` (e.g. `jcc-alameda`). The per-site Pantheon UUIDs live in `.circleci/scripts/project-<site>.sh`.

## How it works (architecture)

### Multisite

* All ~70 courts share one codebase. Each site is a directory under `web/sites/` (e.g. `web/sites/alameda`), plus the Drupal `default` fallback.
* **Routing** is handled by `web/sites/sites.php`:
  * A dynamic matcher maps any host containing a site's directory name to that directory.
  * Custom production domains (e.g. `www.saccourt.ca.gov` → `sacramento`) are mapped explicitly.
  * Local DDEV hostnames are mapped in `web/sites/sites.local.php` (git-ignored; installed from `.ddev/sites.local.php` by `ddev init`). Each site is reachable at `https://<site>.trialcourt.ddev.site`.
* **Per-site settings:** `web/sites/<site>/settings.php` sets a site-specific `file_public_path` (`sites/default/files/<site>/default`) and database name, then includes `settings.local.php`. The local settings files are generated per-site from the template `scripts/local/settings.local.php` by the Composer `createRequiredFiles` script (the `[site]` token is replaced with the directory name).
* **One database per site** locally (named after the site directory), and a **separate Pantheon site per court** in production.

### Hosting (Pantheon)

* Each court maps to its own Pantheon site `jcc-<site>` with its own UUID, defined in `.circleci/scripts/project-<site>.sh` (`UUID`, `SITE_CODE`, `LIVE`).
* Public files are served locally via **Stage File Proxy**, which proxies missing files from each site's live Pantheon environment (`https://live-jcc-<site>.pantheonsite.io`). `origin_dir` is set to the site's `file_public_path` so proxied URLs resolve correctly.

### Theme

* Base theme `jcc_base` plus subthemes (`jcc_components`, `jcc_md`, `jcc_newsroom`, `jcc_webservices`, …) under `web/themes/custom/`.
* Built with **Laravel Mix / webpack** (`npm run production` / `npm run watch`); npm is the single source of truth (no `yarn.lock`).
* Subthemes depend on `jcc_base`, so the DDEV theme commands build `jcc_base` first when targeting a subtheme.

## Local Development

### DDEV Setup

#### Step 1: Install DDEV

**One-time setup — skip if DDEV is already installed.** Follow the [DDEV Installation Guide](https://docs.ddev.com/en/stable/users/install/ddev-installation/):

- **macOS:** `brew install ddev/ddev/ddev`
- **Linux:** download from [GitHub releases](https://github.com/ddev/ddev/releases)
- **Windows:** use WSL2 and follow the Linux instructions

#### Step 2: Project setup

1. Clone this repo into your Projects directory and `cd` into it.
1. Set your Pantheon machine token globally (used to pull databases via Terminus):
   ```bash
   ddev config global --web-environment-add=TERMINUS_MACHINE_TOKEN=your_token_here
   ```
1. Initialize the project:
   ```bash
   ddev init
   ```
   `ddev init` (alias for `ddev project-init`) starts the containers, installs the multisite routing map, runs `composer install`, pulls a database, builds the theme, and logs you in.

### Working with a specific site

Because this is a multisite, most commands need to target a site by its URI:

```bash
# Pull a fresh DB for one court from Pantheon (jcc-<site>) into a local DB named <site>:
ddev refresh alameda

# Log in to a specific site:
ddev drush -l alameda.trialcourt.ddev.site uli

# Run any drush command against a site:
ddev drush -l sf.trialcourt.ddev.site cr
```

`ddev refresh <site>` downloads the latest database backup from `jcc-<site>.live`, imports it into a local database named `<site>`, grants the DDEV `db` user access, and enables Stage File Proxy for that site.

### Available site names

Use any of these as `<site>` (e.g. `ddev refresh alameda`, `https://alameda.trialcourt.ddev.site`). They are the directories under `web/sites/` (excluding Drupal's `default` fallback); each maps to the Pantheon site `jcc-<site>`. There are **70** sites:

| ` ` | ` ` | ` ` | ` ` | ` ` |
| --- | --- | --- | --- | --- |
| `alameda` | `diversity-toolkit` | `madera` | `placer` | `slo2` |
| `alpine` | `elcondado` | `marin` | `plumas` | `solano` |
| `amdr` | `eldorado` | `mariposa` | `riverside` | `sonoma` |
| `appellate` | `fresno` | `md` | `sacramento` | `stanislaus` |
| `butte` | `glenn` | `mendocino` | `sanbenito` | `store-front` |
| `calaveras` | `humboldt` | `merced` | `sanbernardino` | `supremecourt` |
| `cjeo` | `idm` | `modoc` | `sanmateo` | `sutter` |
| `cjer` | `imperial` | `mono` | `santabarbara` | `tehama` |
| `cjer-judicial` | `inyo` | `monterey` | `santaclara` | `trinity` |
| `colusa` | `jrn` | `napa` | `sc` | `tularesuperiorcourt` |
| `contracosta` | `kern` | `nccourt` | `sf` | `tuolumne` |
| `courts` | `kings` | `newsroom` | `shasta` | `ventura` |
| `delnorte` | `lake` | `oc` | `sierra` | `webservices` |
| `deprep` | `lassen` | `partners` | `siskiyou` | `yuba` |

> To regenerate this list: `ls -d web/sites/*/ | xargs -n1 basename | grep -v '^default$' | sort`

### Useful DDEV commands

| Command | Description |
| --- | --- |
| `ddev init` | Full local setup / re-initialize. |
| `ddev refresh <site>` | Pull + import a court's database (add `-f` to force a fresh backup). |
| `ddev db-rebuild` | `composer install` + database refresh. |
| `ddev theme-install [theme]` | Install theme deps and build (defaults to `jcc_base`; `all` for every theme). |
| `ddev theme-build [theme]` | Production build (`jcc_base` first for subthemes; `all` for every theme). |
| `ddev theme-watch [theme]` | Watch and rebuild a theme during development. |
| `ddev drupal-uli` | One-time login link. |
| `ddev pantheon-tickle` | Keep a Pantheon environment awake. |

## Installing modules

Modules are managed with Composer:

```bash
ddev composer require drupal/[module]
```

Enabling/configuring modules is Drupal configuration — export it afterwards:

```bash
ddev drush -l <site>.trialcourt.ddev.site cex -y
```

## Theme development

```bash
ddev theme-install            # install deps + build jcc_base
ddev theme-watch jcc_md       # watch a subtheme while developing
ddev theme-build all          # production build of every theme
```

Node is managed via NVM inside the container; the theme commands set `NODE_OPTIONS=--openssl-legacy-provider` for the webpack 4 / Laravel Mix toolchain.

## Code quality

Static analysis runs in CI in parallel and can be run locally:

```bash
ddev composer phpstan   # PHPStan (mglaman/phpstan-drupal) against custom code
ddev composer rector    # Rector dry-run (palantirnet/drupal-rector) for Drupal deprecations
ddev composer lint      # php -l syntax check of custom code
```

* PHPStan config: `phpstan.neon` (with a `phpstan-baseline.neon` grandfathering existing issues).
* Rector config: `rector.php`.
* A **PHPCS pre-commit hook** (Drupal + DrupalPractice standards) checks staged PHP. Skip in an emergency with `STANDARDS=no git commit …` (not recommended).

## Testing

End-to-end tests use **Cypress** (and Playwright is available):

```bash
ddev cypress-install      # one-time, installs to your local machine
ddev cypress-run          # run the suite
ddev cypress-users        # create test users
```

Cypress runs on the host (not in the container) so it can use local browser options.

## Deployment

Deployment uses a **Parallel Pantheon (GitOps) workflow**. Merging or pushing to an
environment branch triggers CircleCI to build the production artifact and push it to
**every** court's Pantheon repo, then run post-deploy drush (`cr`, `updb`, `cim`, `fra`).
See `docs/git-ops-workflow-deploying-code.md` for the full workflow.

### Branch → environment mapping

| Git branch | Pantheon environment | Purpose |
| --- | --- | --- |
| `develop` | `develop` multidev | Internal QA |
| `stage` | `stage` multidev | Client review / approval |
| `master` | **Live** (production) | Production release (tagged `pantheon_live_N`) |
| `epic-*` | `epic-*` multidev | Persistent environment for a set of dependent features |

Only these branches deploy — the CircleCI filter is `develop`, `stage`, `master`, and `/^epic-.*/`. All other branches (feature branches / PRs) run build, lint, and static analysis only.

### Workflow

1. Branch feature work from **`master`** (keep it clean): `feature/<ticket>--short-description`.
1. Open a PR and merge into **`develop`** → deploys to the `develop` multidev for internal QA.
1. Merge the clean feature branch into **`stage`** → deploys to the `stage` multidev for client approval.
1. Merge the approved feature branch into **`master`** → deploys to **Live**.

> Resolve merge conflicts **locally** by merging the feature into the environment branch — never via GitHub's "resolve conflicts" UI, which rebases the base branch into your feature and contaminates it with unapproved code.

### Epic (persistent) environments

For a set of interdependent features, create an `epic-<name>` multidev on Pantheon
(**≤ 12 characters, no `/`** — e.g. `epic-cart`), branch it from `master`, and push.
The epic is later run through `develop` → `stage` → `master` like a normal feature.

### Per-site deploy configuration

CircleCI deploys all courts via a job matrix. Each court's deploy target is configured in
`.circleci/scripts/project-<site>.sh`:

```bash
UUID=<pantheon-site-uuid>   # the jcc-<site> Pantheon site
SITE_CODE=<site>            # e.g. alameda
LIVE=true                   # tag a Live release on master deploys
```

## Related documentation

* `docs/git-ops-workflow-deploying-code.md` — full deployment / branching workflow
* `docs/MULTISITE.md` — multisite details
* `docs/creating-new-sites.md` — adding a new court
* `docs/local-development.md` — local environment notes
* `docs/local-solr-search.md` — Solr setup
* `docs/migrations.md` — migration process
