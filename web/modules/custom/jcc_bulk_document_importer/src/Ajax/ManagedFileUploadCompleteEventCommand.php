<?php

namespace Drupal\jcc_bulk_document_importer\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Command to trigger an event when managed file upload is complete.
 */
class ManagedFileUploadCompleteEventCommand implements CommandInterface {

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'triggerManagedFileUploadComplete',
    ];
  }

}
