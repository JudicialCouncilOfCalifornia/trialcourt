<?php
namespace Drupal\jcc_migrate_pdf\Command;

use Drush\Commands\DrushCommands;

/**
 * Drush command file.
 */
class JccMigratePdfCommands extends DrushCommands
{

    /**
     * @command jcc-migrate-pdf:custom-command
     * @aliases jmcc
     * @description My custom Drush command description.
     */

    public function printMe()
    {

        return 'hi';}
}
