<?php

namespace Drupal\my_migrate_plugin\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

/**
 * Provides a process plugin for looking up media ID by file URL.
 *
 * @MigrateProcessPlugin(
 *   id = "lookup_media_from_file"
 * )
 */
class LookupMediaFromFile extends ProcessPluginBase {

  /**
   * Transforms the source file URL into a media entity ID.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      return NULL;
    }
    $path_parts = parse_url($value);
    if (!isset($path_parts['path'])) {
      return NULL;
    }
    $filename = basename($path_parts['path']);    
    $public_path = 'public://legislative_reports/' . $filename;    
    $files = \Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $public_path]);
    if ($files) {
      $file = reset($files);            
      $media = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties([
        'field_media_file.target_id' => $file->id(),
      ]);
      if ($media) {
        $media_entity = reset($media);
        \Drupal::logger('lookup_media_from_file')->notice('media found for file: @file', ['@file' => $mid]);
        \Drupal::messenger()->addMessage('Found media ID: ' . $mid);
        return $media_entity->id();
      }
    }
    return NULL;
  }
}
