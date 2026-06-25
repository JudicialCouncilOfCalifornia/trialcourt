# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A **Drupal 9 multisite** (`drupal/core-recommended` 9.5.x, PHP 8.1) that runs ~70 California court websites from one codebase, hosted on **Pantheon** (one Pantheon site per court), developed locally with **DDEV**, deployed via **CircleCI**.

## The multisite model (read this first)

This is the single most important thing to understand — almost everything is per-site.

- Each court is a directory under `web/sites/` (e.g. `web/sites/alameda`), plus Drupal's `default` fallback. There is **one local database per site, named after the directory** (`alameda`, `sf`, …).
- **Each court is a separate Pantheon site** named `jcc-<site>` (e.g. `jcc-alameda`), each with its own UUID in `.circleci/scripts/project-<site>.sh`.
- **Routing** (`web/sites/sites.php`): a dynamic matcher maps hosts containing a site's directory name to that directory; production domains (e.g. `www.saccourt.ca.gov` → `sacramento`) are mapped explicitly. Locally, `web/sites/sites.local.php` maps `<site>.trialcourt.ddev.site` → directory.
- **Per-site settings**: `web/sites/<site>/settings.php` sets a site-specific `file_public_path` (`sites/default/files/<site>/default`) and DB name, then includes `settings.local.php`.

### Generated files — do not hand-edit per copy

- `web/sites/<site>/settings.local.php` (git-ignored) is generated for every site from the template **`scripts/local/settings.local.php`** by Composer's `DrupalProject\composer\ScriptHandler::createRequiredFiles` (runs on `composer install`/`update`), replacing the `[site]` token. **Edit the template, not the 70 copies.**
- `web/sites/sites.local.php` (git-ignored) is installed from **`.ddev/sites.local.php`** by `ddev init`.

### Consequence: target a site explicitly

Most Drush/site commands need a URI, or they hit `default`:

```bash
ddev drush -l alameda.trialcourt.ddev.site cr
ddev drush -l alameda.trialcourt.ddev.site uli
```

## Common commands

```bash
# First-time / full local setup (containers, composer, db, theme, login)
ddev init                      # alias of: ddev project-init

# Pull one court's DB from Pantheon (jcc-<site>.live) into local DB <site>,
# grant the ddev `db` user access, and enable Stage File Proxy for it.
ddev refresh alameda           # add -f to force a fresh backup
ddev db-rebuild                # composer install + db refresh

# Static analysis (also runs in CI, in parallel)
ddev composer phpstan          # mglaman/phpstan-drupal, level 1 + baseline
ddev composer rector           # palantirnet/drupal-rector, dry-run
ddev composer lint             # php -l on custom code

# Tests (run on the host, not in containers)
ddev cypress-install           # one-time
ddev cypress-run
ddev playwright-run
```

### Theme

The base theme `jcc_base` plus subthemes (`jcc_components`, `jcc_md`, `jcc_newsroom`, `jcc_webservices`, …) live in `web/themes/custom/`. Built with **Laravel Mix / webpack 4** via **npm** (the project is npm-only; there is no `yarn.lock`). Subthemes depend on `jcc_base`, so the commands build/install `jcc_base` first when targeting a subtheme.

```bash
ddev theme-install [theme|all] # npm install + production build (default: jcc_base)
ddev theme-build  [theme|all]  # production build (npm run production)
ddev theme-watch  [theme]      # npm run watch
```

The npm scripts are `production`/`watch` (there is **no `build` script**). On Node ≥17 the commands set `NODE_OPTIONS=--openssl-legacy-provider` for the webpack 4 toolchain.

## Code quality gates

> **All generated code must pass the project's coding standards before a task is considered complete.** After writing or editing code, run the relevant checks and fix every violation — do not leave them for the user. For PHP that means PHPCS (Drupal + DrupalPractice), and PHPStan/Rector for custom modules/themes (see below); this includes repo-root PHP like `rector.php`, which the pre-commit hook also checks. Match the surrounding code style.

- **PHPCS pre-commit hook** runs Drupal + DrupalPractice standards on staged `*.php`/`*.inc`/`*.module`/etc. Fix violations (or `composer phpcbf`-style) before committing; in an emergency, `STANDARDS=no git commit …` skips it (discouraged). New PHP files at the repo root (e.g. `rector.php`) are also checked.
- **PHPStan** config: `phpstan.neon` (level 1 + `phpstan-baseline.neon` grandfathering existing issues; a few un-analyzable files are in `excludePaths`). **Rector** config: `rector.php`. Both scan `web/{modules,themes}/custom`.

## Deployment (Parallel Pantheon / GitOps)

Pushing/merging to an environment branch triggers CircleCI to build the production artifact and push it to **every court's** Pantheon repo, then run post-deploy drush (`cr`, `updb`, `cim`, `fra`). Only these branches deploy (CircleCI filter `develop`, `stage`, `master`, `/^epic-.*/`); all other branches run build/lint/static-analysis only.

| Branch | Pantheon environment |
| --- | --- |
| `develop` | `develop` multidev (internal QA) |
| `stage` | `stage` multidev (client approval) |
| `master` | **Live** (production; tagged `pantheon_live_N`) |
| `epic-*` | `epic-*` multidev (persistent; ≤12 chars, no `/`) |

Workflow: branch features from **`master`** (`feature/<ticket>--desc`), merge to `develop` → `stage` → `master`. Resolve merge conflicts **locally**, never via GitHub's UI. Per-site deploy targets live in `.circleci/scripts/project-<site>.sh` (`UUID`, `SITE_CODE`, `LIVE`); the deploy logic is `.circleci/scripts/deploy.sh` (sets `PANTHEON_ENV=$CIRCLE_BRANCH`, `master`→`live`).

## Key references

- `docs/Kanopi Devops.md` — DevOps + local dev overview
- `docs/git-ops-workflow-deploying-code.md` — full branching/deploy workflow
- `docs/MULTISITE.md`, `docs/creating-new-sites.md` — multisite + adding a court
- `docs/migrations.md`, `docs/local-solr-search.md` — migrations, Solr
- `.ddev/scripts/refresh-pantheon.sh` — the `ddev refresh <site>` implementation
