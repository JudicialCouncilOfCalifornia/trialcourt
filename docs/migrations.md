## Migration

Run migrations on live where content creation will happen, pre-launch.

https://docs.google.com/spreadsheets/d/1zsZ-cEIZGWvmv0dXVTyL8Hh4Y9ld64Gcqy3TuxQAP7c/edit?pli=1#gid=0

- run migrations:
  - term_subject_matter - built in csv
  - Set sheet urls in `/admin/structure/migrate/jcc_migrate_source_ui`
    - https://sheets.googleapis.com/v4/spreadsheets/{{ sheet id }}/values/{{ tab_name }}?alt=json&key={{ apikey}}
      Note: get an API key for Google Sheets API v4 here: https://console.developers.google.com/apis/credentials (create an empty project for it).
    - Set tab in the following order for the appropriate sheet name in the "tag" set for each migration.
      - "forms"
        - forms_file
        - forms_file_path_redirect
        - forms_media
      - "page" (depends on "forms")
        - node_subpage
        - node_subpage_path_redirect
      - "news"
        - node_news (optional if available)
    - Run migrations in the following order by tag name
      - `lando drush @[site].[env] mim --tag=forms`
      - `lando drush @[site].[env] mim --tag=subpage` (again, depends on forms)
      - `lando drush @[site].[env] mim node_news` (if needed)
      - OR `lando drush @[site].[env] mim --all` (ok at the time of writing)
      - **NOTE:** Local environments use `@local.[site]` as the site selector.


---
## Migrating Content

### Commandline

It’s easiest to use the repo's drush aliases via lando, if you haven’t installed pantheon wide aliases globally. (If you have, use your own aliases if you want.)

Use `lando drush sa` for a list of available aliases.

 - `lando drush @[site].[env] ms` - migration status will show you any migrations and their status.
 - `lando drush @[site].[env] mim --tag=[tag]` - multiple migration imports categorized by tag.
 - `lando drush @[site].[env] mim [migration]` - migration import the specific migration.

 - Alternatively:
   - `lando drush @[site].[env] mim --group jcc` - migration import all of jcc group migrations.
   - `lando drush @[site].[env] mim --all` - migration import of all migrations.

You should see import progress.

##### See: `drush mim --help` for more tools, like --limit to import a set number of items. Or --feedback

##### See: `drush | grep migrate` for more command info.

#### Other Migrate Commands:

- `drush mr [migration]` - Roll Back a migration.
- `drush mim --update [migration]` - Update an imported migration if data changes.
- `drush mst [migration] && drush mrs [migration]` - First STOP and then RESET status of a stuck migration.

Import options used by drush from Migrate Tools: https://www.drush.org/11.x/commands/migrate_import/

### Drupal UI (Beta)

 - Navigate to “Structure > Migrations” https://stage-jcc-tc.pantheonsite.io/admin/structure/migrate
 - click things… er…
   - On JCC, click “List Migrations”
   - On [migration], click “Execute”

OR
 - Navigate to "Structure > Migrations > JCC Migrate Source UI"
 - Here you can upload files or set urls for migrations with existing migrators.

 NOTE: This custom module stopped migrating at some point and I never had a chance to debug. You still need to use it to set the urls for the migrations listed above, but you can't execute a migration from here. Use one of the other two methods described.

## Forms File

This migration is a dependency for many of the other migrations. It must import fully for the other migrations to import.

This can require cleanup of the Forms Files sheet. Specifically the `url` column which is the unique id for the migration.  Make sure it contains no duplicates.

The initial scrape from JCC contains only "Local Forms" with titles and other data. This was the original intent of this migrator. It evolved into a migrator of all files linked on the old site. Additional file urls come from a crawl Steph (Chapter Three) has been doing to fetch all file links in content. This is where the duplicates come from, but also a lot of urls that are missing from "Local Forms".

  On the URL column:

  - search and replace `http://` -> `https://`
  - search and replace `https://` -> `https://www.`
    - The above ensures all urls start with `https://www.`
    - Use search and replace `https://www.` -> `https://` if www is not accessible?
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

## Other notes

- It's useful to disable the search index while doing the imports and re-enable it afterwards (avoids some notices on your local).

## Migration Configurations

How to create additional migration profiles through configuration files for [Migration Plus](https://www.drupal.org/project/migrate_plus.

 - If not an immutable profile, log into a Drupal site and go to `Structure > Migrations` or `/admin/structure/migrate`
 - Use `Add migration group` to create a new migration group, named contextually for specific site use (e.g. the site name). This name will be specified as the `migration_group` for the profile configurations.
 - If an immutable profile, you will specify `jcc2` as the `migration_group` in the profile configurations.
   - Under `config/config-sitename`, create/copy a new `migrate_plus.migration.\[profile_name]\.yml file (i.e. migrate_plus.migration.courts_node_job.yml).
     - [Configuration example](../config/config-courts/migrate_plus.migration.courts_node_job.yml) migrating XML source into content type.
     - <details>
         <summary>Notable configuration details & examples</summary>

         ```
         id: ... same `[profile_name]` as the file name
         migration_tags: ... `[profile_name]` broken up by underscore delimiter
           - profile
           - name
         migration_group: ... grouping name
         label: ... Displayed in at `/admin/structure/migrate`
         source: ... the content origin
           plugin: ... typically `url` but can be file type like `csv`
           data_fetcher_plugin: ... `http` (typically) or `file` if the origin is local to the site
           data_parser_plugin: ... origin format such as `google_sheet` or `xml`
           urls: ... origin location such as URL or file path and can be multiple locations
           item_selector: ... where in the origin's structure to import (e.g. /root/jcc_apl_tao)
             fields: ... identifying content to import by tag/key/column
               -
                 name: ... field name same as `selector` below to match context
                 label: ... for display purposes
                 selector: ... tag/key/column
               -
                 name: ...
                 label: ...
                 selector: ...
               -
                 ... (repeat as many selectors as needed)
             ids: ... use origin selector from above as a unique identifier during import
               selector
                 type: ... value type such as `string` or `integer`
         process: ... Drupal entity destination/data mapping processing
           moderation_state: ... publish state on import example
             plugin: ... default_value
             default_value: ... published
           uid:
             plugin: default_value
             default_value: 1
           title:
             -
               plugin: get
               source: title ... origin selector
             -
               plugin: default_value
               default_value: TITLE
           path/pathauto:
             plugin: default_value
             default_value: 1
           body/value: ... combining multiple source example
             plugin: concat
             source:
               - description
               - constants/break
               - url
           body/format:
             plugin: default_value
             default_value: body
           field_department: ... import as taxonomy example
             -
               plugin: skip_on_empty
               method: process ... sets as null if empty or can skip entire record if set to `row`
               source: department
             -
               plugin: entity_generate ... assigns or creates term in Drupal
               entity_type: taxonomy_term
               bundle_key: vid
               bundle: department
               value_key: name
               ignore_case: true
           field_date/value: ... date import example
             -
               plugin: callback
               callable: strtotime ... convert to server time
               source: open_date
             -
               plugin: format_date
               from_format: U
               to_format: Y-m-d
           field_is_temporary:  ... boolean based on value example
             plugin: evaluate_condition
             source: reg_temp
             condition:
               plugin: equals
               value: Temporary
           field...  (repeat as many migration processes as needed)
         destination: ... entity target example
           plugin: 'entity:node'
           default_bundle: job
         migration_dependencies: null
         ```
         </details>

Documentation:
 - [Migration 101](https://drupalmigrate.org/)
 - [Migrate API](https://www.drupal.org/docs/drupal-apis/migrate-api)
   - [Migrate Examples](https://www.drupal.org/docs/drupal-apis/migrate-api/migrate-destination-plugins-examples)
 - [Migrate Conditions](https://www.drupal.org/docs/8/api/migrate-api/migrate-process-plugins/migrate-conditions/migrate-conditions-process-plugins) - requires separate module add-on

## Automating Migrations

Schedule migrations when the content needs to be updated periodically. For example, case information or job postings are provided by separate systems so that content needs to be imported constantly in bulk.
We are currently using GitHub actions to schedule migration jobs through the hosting service, currently Pantheon.

 - [Git clone the terminus-actions repos](https://github.com/JudicialCouncilOfCalifornia/terminus-actions)
 - Create/Copy a new workflow configuration at `.github/workflows`.
 - Add terminus commands for the workflow to execute the migration profiles as described in this document. [Example](https://github.com/JudicialCouncilOfCalifornia/terminus-actions/blob/main/.github/workflows/migration-cases.yml)
 - [Monitor the scheduled migration](https://github.com/JudicialCouncilOfCalifornia/terminus-actions/actions) from the `Actions` tab in the GitHub project.

## Missing Pieces

There was discussion about migrating additional taxonomy references with `forms_file` but that was never fully defined. Works needs to be done to flesh the rest of the taxonomy structure out and ensure the appropriate entities have the appropriate taxonomy reference fields.
