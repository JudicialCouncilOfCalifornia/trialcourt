<?php

namespace Drupal\jcc_elevated_custom\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for migrating publication files into single file field.
 */
class PublicationFileMigrationSuccessController extends ControllerBase {

  /**
   * Returns a simple message.
   *
   * @return array
   *   A render array containing the message.
   */
  public function message() {

    return array([
      '#markup' => 'First files for all publications have been migrated to a single field.',
    ]);

  }
}
