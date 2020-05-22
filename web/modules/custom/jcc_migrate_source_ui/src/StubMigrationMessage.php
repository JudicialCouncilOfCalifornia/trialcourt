<?php

namespace Drupal\jcc_migrate_source_ui;

use Drupal\migrate\MigrateMessageInterface;

class StubMigrationMessage implements MigrateMessageInterface {

  /**
     * Output a message from the migration.
     *
     * @param string $message
     *   The message to display.
     * @param string $type
     *   The type of message to display.
     *
     * @see drupal_set_message()
     */
  public function display($message, $type = 'status') {
    \Drupal::messenger()->addMessage($message, $type);
  }

}
