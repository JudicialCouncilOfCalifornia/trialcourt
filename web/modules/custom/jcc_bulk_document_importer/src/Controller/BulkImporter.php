<?php

namespace Drupal\jcc_bulk_document_importer\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Deletes all groups from user.
 */
class BulkImporter extends ControllerBase {

  /**
   * Returns a render-able array.
   */
  public function content() {

    $build = [
      '#markup' => '
      <div class="jcc_generator_embed">
        <h1 class="jcc-title">test</h1>
      </div>
      ',
    ];

    return $build;
  }

}
