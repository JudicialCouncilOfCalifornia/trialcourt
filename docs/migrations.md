### Migration

Run migrations on live where content creation will happen, pre-launch.

https://docs.google.com/spreadsheets/d/1zsZ-cEIZGWvmv0dXVTyL8Hh4Y9ld64Gcqy3TuxQAP7c/edit?pli=1#gid=0

- run migrations:
  - term_subject_matter - built in csv
  - Set sheet urls in `/admin/structure/migrate/jcc_migrate_source_ui`
    - https://spreadsheets.google.com/feeds/list/{{ sheet id }}/{{ tab }}/public/values?alt=json
    - Set tab for the appropriate sheet number in the set for each migration
      - node_subpage, node_subpage_path_redirect (1 at time of writing)
      - node_news (2 at time of writing)
      - forms_file, forms_file_path_redirect, forms_media (4 at time of writing)
    - run migrations
      - ... mim --tag=subpage
      - ... mim node_news
      - ... mim --tag=forms
      - OR ... mim --all (ok at the time of writing)


---
## Migrating Content

### Commandline

It’s easiest to use the repo's drush aliases via lando, if you haven’t installed pantheon wide aliases globally. (If you have, use your own aliases if you want.)

Use `lando drush sa` for a list of available aliases.

 - `lando drush @[site].[env] ms` - migration status will show you any migrations and their status.
 - `lando drush @[site].[env] mim [migration]` - migration import the specific migration.

 - Alternatively:
   - `lando drush @[site].[env] mim --group jcc` - migration import all of jcc group migrations.
   - `lando drush @[site].[env] mim --all` - migration import of all migrations.

You should see import progress.

##### See: `drush mim --help` for more tools, like --limit to import a set number of items. Or --feedback

##### See: `drush | grep migrate` for more command info.

### Drupal UI

 - Navigate to “Structure > Migrations” https://stage-jcc-tc.pantheonsite.io/admin/structure/migrate
 - click things… er…
   - On JCC, click “List Migrations”
   - On [migration], click “Execute”

OR
 - Navigate to "Structure > Migrations > JCC Migrate Source UI"
 - Here you can upload files or set urls for migrations with existing migrators.

 - Verify content imported correctly.

### Other Migrate Commands:

 - `drush mr [migration]` - Roll Back a migration.
 - `drush mim --update [migration]` - Update an imported migration if data changes.
 - `drush mst [migration] && drush mrs [migration]` - First STOP and then RESET status of a stuck migration.
