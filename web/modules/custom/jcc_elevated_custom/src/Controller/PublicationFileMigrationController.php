<?php

namespace Drupal\jcc_elevated_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Url;
use Drupal\redirect\Entity\Redirect;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for migrating publication files into single file field.
 */
class PublicationFileMigrationController extends ControllerBase {

  /**
   * Callback when the batch process completes.
   */
  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      \Drupal::messenger()->addMessage(t('Batch processing complete! Processed @count items.', ['@count' => count($results) * 200]));
    }
    else {
      \Drupal::messenger()->addMessage(t('Batch processing encountered errors.'), 'error');
    }

    // Redirect to custom page upon completion.
    $url = Url::fromRoute('jcc_elevated_custom.publication_file_migration_success')->toString();
    $response = new RedirectResponse($url);
    $response->send();
  }

  /**
   * Processes each item.
   * Migrates publication primary file to a single file field.
   */
  public static function processItem($batch, $current_key, $total_pubs, &$context) {
    // Track progress.
    $context['message'] = 'Processing media items : ' . $current_key . ' / ' . $total_pubs;
    $context['results'][] = $batch;

    $publications = \Drupal::entityTypeManager()->getStorage('media')->loadMultiple($batch);
    foreach ($publications as $publication) {
      $current_values = $publication->get('field_media_file_multiple')->getValue();
      if (!empty($current_values)) {
        // Storing the current path.
        $old_file_url = '';
        $old_file = $publication->get('field_media_file_multiple')->entity;
        if ($old_file->getEntityTypeId() === 'file') {
          $old_file_url = file_url_transform_relative(file_create_url($old_file->getFileUri()));
        }

        // Move first file to diff field.
        $publication->set('field_media_file', $current_values[0]);
        // Remove first file from previous.
        array_shift($current_values);
        $publication->set('field_media_file_multiple', $current_values);
        try {
          $publication->save();
          // Need to reload the publication to get the new file path.
          $reloaded_publication = \Drupal::entityTypeManager()->getStorage('media')->load($publication->id());
          $new_file = $reloaded_publication->get('field_media_file')->entity;
          if ($new_file->getEntityTypeId() === 'file') {
            $new_file_url = file_create_url($new_file->getFileUri());

            $redirect = Redirect::create([
              'redirect_source' => $old_file_url,
              'redirect_redirect' => $new_file_url,
              'status_code' => 301,
            ]);
            $redirect->save();
          }
        }
        catch (EntityStorageException $exception) {
          \Drupal::logger('jcc_elevated_custom')->error('jcc_elevated_update_9001: ' . $exception->getMessage());
        }
      }
    }
    sleep(5);
  }

  /**
   * Returns a simple message.
   *
   * @return array
   *   A render array containing the message.
   */
  public function migrate() {

    // Define batch operations.
    $progressbatch = [
      'title' => t('Processing Items'),
      'operations' => [],
      'finished' => [self::class, 'batchFinished'],
    ];

    $mids = \Drupal::entityQuery('media')->condition('bundle', 'publication')->accessCheck(FALSE)->execute();
    $batch_size = 200;
    $batches = array_chunk($mids, $batch_size);

    foreach ($batches as $key => $batch) {
      $current_key = $key * $batch_size;
      $total_pubs = count($batches) * $batch_size;

      $progressbatch['operations'][] = [
        [self::class, 'processItem'],
        [$batch, $current_key, $total_pubs],
      ];
    }
    // Set the batch.
    batch_set($progressbatch);

    return batch_process('/publication-file-migration-complete');
  }

}
