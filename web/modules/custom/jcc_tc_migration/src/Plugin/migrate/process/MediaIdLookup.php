<?php

namespace Drupal\jcc_tc_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Lookup Media ID from a file or remote URL.
 *
 * @MigrateProcessPlugin(
 *   id = "media_id_lookup"
 * )
 */
class MediaIdLookup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    if (empty($value)) {
      return NULL;
    }
    $filename = $value;
    \Drupal::logger('filename')->warning('Printing filename here: @filename', ['@filename' => $value]);

    if (empty($filename)) {
      \Drupal::logger('media_id_lookup')->warning('Filename could not be extracted from URL: @url', ['@url' => $value]);
      return NULL;
    }
    $file_storage = \Drupal::entityTypeManager()->getStorage('file');
    $query = $file_storage->getQuery();
    $query->condition('uri', '%' . $filename, 'LIKE');
    $fids = $query->execute();
    if (empty($fids)) {
      \Drupal::logger('media_id_lookup')->warning('No file entity found with filename: @filename', [
        '@filename' => $filename,
      ]);
      return NULL;
    }
    $fid = reset($fids);
    \Drupal::logger('media_id_lookup')->notice('Found file ID @fid for file @filename', [
      '@fid' => $fid,
      '@filename' => $filename,
    ]);
    $media_storage = \Drupal::entityTypeManager()->getStorage('media');

    $media_ids_pub = $media_storage->getQuery()
      ->condition('bundle', 'publication')
      ->condition('field_media_file_multiple.target_id', $fid)
      ->range(0, 1)
      ->execute();
    $media_ids_other = $media_storage->getQuery()
      ->condition('field_media_file.target_id', $fid)
      ->range(0, 1)
      ->execute();
    $media_ids = $media_ids_pub + $media_ids_other;
    if (empty($media_ids)) {
      \Drupal::logger('media_id_lookup')->warning('No media found for fid @fid', ['@fid' => $fid]);
      return NULL;
    }
    $mid = reset($media_ids);
    \Drupal::logger('media_id_lookup')->notice('Found media ID @mid for file @filename', [
      '@mid' => $mid,
      '@filename' => $filename,
    ]);
    return $mid;
  }

}
