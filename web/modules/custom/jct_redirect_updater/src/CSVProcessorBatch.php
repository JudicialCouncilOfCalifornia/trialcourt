<?php

namespace Drupal\jct_redirect_updater;

use Drupal\redirect\Entity\Redirect;
use Drupal\file\Entity\File;
use Drupal\Core\Messenger\MessengerInterface;

class CSVProcessorBatch {

  /**
   * Processes CSV items for redirects.
   *
   * @param string $file_uri
   *   The URI of the file to process.
   * @param array $context
   *   The batch context.
   */
  public static function processItems($file_uri, &$context) {
    $messenger = \Drupal::messenger();
    $file_system = \Drupal::service('file_system');
    $real_path = $file_system->realpath($file_uri);

    if (!file_exists($real_path) || !is_readable($real_path)) {
      $messenger->addError(t('The file at @path is not readable or does not exist.', ['@path' => $real_path]));
      return;
    }

    $handle = fopen($real_path, 'r');
    if (!$handle) {
      $messenger->addError(t('Failed to open file for reading: @path', ['@path' => $real_path]));
      return;
    }

    // Initialize or resume batch processing.
    if (empty($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['pointer'] = 0;
      $total = 0;
      while (fgetcsv($handle)) {
        $total++;
      }
      rewind($handle); // Go back to the beginning of the file after counting.
      $context['sandbox']['max'] = $total - 1; // Assume first row is header.
    }

    // Process a chunk of rows.
    $limit = $context['sandbox']['pointer'] + 25;
    while ($context['sandbox']['pointer'] < $limit && ($row = fgetcsv($handle)) !== FALSE) {
      self::updateRedirect($row[0], $row[1]); // Column 0 is old path, Column 1 is new path.
      $context['sandbox']['progress']++;
      $context['sandbox']['pointer']++;
    }

    fclose($handle);

    $context['message'] = t('Processed @count of @total.', [
      '@count' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['max'],
    ]);

    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }

  /**
   * Updates or creates a redirect from an old path to a new path.
   *
   * @param string|array $old_path
   *   The old path, which needs to be a string.
   * @param string $new_path
   *   The new path.
   */
  private static function updateRedirect($old_path, $new_path) {
    $messenger = \Drupal::messenger();

    // Ensure $old_path is a string.
    if (is_array($old_path)) {
      $old_path = print_r($old_path, TRUE);
    }

    $redirects = \Drupal::entityTypeManager()
      ->getStorage('redirect')
      ->loadByProperties(['redirect_source__path' => $old_path]);

    if (!empty($redirects)) {
      $redirect = reset($redirects);
      $redirect->setRedirect($new_path);
      $redirect->save();
      $messenger->addMessage(t('Redirect updated successfully for @path.', ['@path' => $old_path]));
    } else {
      $messenger->addWarning(t('No redirect found for @path, skipping.', ['@path' => $old_path]));
    }
  }

  /**
   * Finishes the batch processing and deletes the processed file.
   *
   * @param bool $success
   *   A boolean indicating whether the batch completed successfully.
   * @param mixed $results
   *   The results of the batch.
   * @param array $operations
   *   Any operations that were not completed.
   */
  public static function finished($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $messenger->addMessage(t('All redirects have been updated successfully.'));
    } else {
      $error_operation = reset($operations);
      $messenger->addError(t('An error occurred while processing @operation.', ['@operation' => print_r($error_operation, TRUE)]));
    }

    // Assuming the file URI is passed correctly through the batch process results
    if (!empty($results['file_uri'])) {
      $file = File::load($results['file_uri']);
      if ($file && file_exists($file->getFileUri())) {
        $file->delete();
        $messenger->addMessage(t('Uploaded file has been deleted.'));
      } else {
        $messenger->addError(t('Failed to delete the uploaded file.'));
      }
    }
  }
}
