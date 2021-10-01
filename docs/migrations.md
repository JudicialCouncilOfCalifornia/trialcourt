### Migration

Run migrations on live where content creation will happen, pre-launch.

https://docs.google.com/spreadsheets/d/1zsZ-cEIZGWvmv0dXVTyL8Hh4Y9ld64Gcqy3TuxQAP7c/edit?pli=1#gid=0

- run migrations:
  - term_subject_matter - built in csv
  - Set sheet urls in `/admin/structure/migrate/jcc_migrate_source_ui`
    - https://sheets.googleapis.com/v4/spreadsheets/{{ sheet id }}/values/{{ tab_name }}?alt=json&key={{ apikey}}
      Note: get an API key for Google Sheets API v4 here: https://console.developers.google.com/apis/credentials (create an empty project for it).
    - Set tab for the appropriate sheet name in the set for each migration
      - node_subpage, node_subpage_path_redirect ("page" at time of writing)
      - node_news ("news" at time of writing)
      - forms_file, forms_file_path_redirect, forms_media ("forms" at time of writing)
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

 NOTE: This custom module stopped migrating at some point and I never had a chance to debug. You still need to use it to set the urls for the migrations listed above, but you can't execute a migration from here. Use one of the other two methods described.

### Other Migrate Commands:

 - `drush mr [migration]` - Roll Back a migration.
 - `drush mim --update [migration]` - Update an imported migration if data changes.
 - `drush mst [migration] && drush mrs [migration]` - First STOP and then RESET status of a stuck migration.

## Forms File

This migration is a dependency for many of the other migrations. It must import fully for the other migrations to import.

This can require cleanup of the Forms Files sheet. Specifically the `url` column which is the unique id for the migration.  Make sure it contains no duplicates.

The initial scrape from JCC contains only "Local Forms" with titles and other data. This was the original intent of this migrator. It evolved into a migrator of all files linked on the old site. Additional file urls come from a crawl Steph (Chapter Three) has been doing to fetch all file links in content. This is where the duplicates come from, but also a lot of urls that are missing from "Local Forms".

  On the URL column:

  - search and replace `http://` -> `https://`
  - search and replace `https://www.` -> `https://`
  - search and replace `https://` -> `https://www.`
    - The above ensures all urls start with `https://www.`
  - search and replace (regex) `\?.*` -> (empty) to remove queries from the urls.

This should make all the urls in a standard format so you can search for duplicates.

  - sort table by the `url` column so any duplicate urls will be in rows next to each other.
  - Some rows have title and other data.
  - Where you see a duplicate url, keep the one with the title and additional information.
  - I just quickly, visually scan the list for rows with titles next to rows without titles, it's easy to spot those gaps. Then check if there is a duplicate.
  - Once these duplicates are cleaned up you can use the "Data > Remove duplicates" tool to remove any other duplicates on the `url` column.
    - Check off the "has header row" option.
    - Uncheck all the columns except `url`

When you run the migration, if there are any fails for forms_file after this, it could be because the URL gives a 404.

`lando drush @alias mmsg forms_file` to get any messages from the migration. Troubleshoot and fix if you can, or just remove that url from the sheet if you can. A few missing files can be managed in the CMS later.

### Function

 - `forms_file` creates file entities, downloading the original file from the url into Drupal's file system.
 - `forms_media` creates media entities that reference their corresponding file entities.
 - `forms_file_path_redirect` creates redirects in Drupal so any legacy links to the old file paths will redirect to the new file locations.
 - `node_subpage` depends on `forms_media` because it takes the hardcoded file links in content and replaces them with embedded media links instead.
   - @see `web/modules/custom/jcc_tc_migration/src/Services/MediaReplaceFileLink.php` which does the search and replace.
   - @see `web/modules/custom/jcc_tc_migration/src/Plugin/migrate/process/MediaReplaceFileLink.php` which is just a migrate process plugin wrapper for the service.
   - The service was created because the intention is to run the same process on content that was migrated previously in the earlier sites we set up. Use this service in an update hook that loads appropriate entities or fields and replace the links.
   - Body content is migrated into the node body field. This field only renders when there are no Paragraphs on the node. The body field can not be used by editors to add content to a new node. (Same for news nodes).
 - `node_subpage_path_redirect` creates redirects from old page urls to their new Drupal equivalents.

## Missing Pieces

There was discussion about migrating additional taxonomy references with `forms_file` but that was never fully defined. Works needs to be done to flesh the rest of the taxonomy structure out and ensure the appropriate entities have the appropriate taxonomy reference fields.
