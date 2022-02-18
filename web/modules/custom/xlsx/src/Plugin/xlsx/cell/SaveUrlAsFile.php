<?php

namespace Drupal\xlsx\Plugin\xlsx\cell;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\xlsx\Plugin\XlsxCellBase;
use Drupal\xlsx\Entity\XlsxEntityMapping;

/**
 * Download remote file and save it as File entity.
 *
 * @XlsxCell(
 *   id = "save_url_as_file",
 *   name = @Translation("Save URL As File"),
 *   description = @Translation("Download remote file, save it as a File entity and attach to an entity."),
 *   field_types = {
 *     "file",
 *     "image",
 *   }
 * )
 */
class SaveUrlAsFile extends XlsxCellBase {

  /**
   * This could be also changed to obtain file upload dir form the field settings.
   */
  protected $upload_directory = 'public://xlsx/files';

  /**
   * {@inheritdoc}
   */
  public function import($entity, $field_name, $value, $mapped_fields) {
    if ($entity->hasField($field_name) && !empty($value)) {
      $path = parse_url($value, PHP_URL_PATH);
      $filename = basename($path);
      $hash = md5($filename);
      $mapping_entities = XlsxEntityMapping::loadByProperties(['hash' => $hash]);
      if ($mapping = reset($mapping_entities)) {
        return ['target_id' => $mapping->getMapEntityId()];
      }
      else {
        // Prepare directory.
        \Drupal::service('file_system')->prepareDirectory($this->upload_directory, FileSystemInterface::CREATE_DIRECTORY);
        if ($file = system_retrieve_file($value, $this->upload_directory . '/' . $filename, TRUE)) {
          // Keep track of imported files. This will prevent file duplication in the future.
          // We checking file hash to make sure it is unique. Not the best apporach as a generic solution
          // but a good start for a custom plugin.
          $mapping = XlsxEntityMapping::create([]);
          $mapping->setMapEntityType('file', 'file');
          $mapping->setMapEntityId($file->id());
          $mapping->setMapHash($hash);
          $mapping->save();
          return ['target_id' => $file->id()];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function export($entity, $field_name, $value) {
    if ($entity->hasField($field_name) && !empty($value[0]['target_id'])) {
      if ($file = File::load($value[0]['target_id'])) {
        return Url::fromUri($file->createFileUrl(FALSE), ['absolute' => TRUE])->toString();
      }
    }
  }

}
