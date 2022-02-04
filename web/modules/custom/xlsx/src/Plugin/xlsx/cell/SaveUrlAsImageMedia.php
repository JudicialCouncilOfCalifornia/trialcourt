<?php

namespace Drupal\xlsx\Plugin\xlsx\cell;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\xlsx\Plugin\XlsxCellBase;
use Drupal\xlsx\Entity\XlsxEntityMapping;

/**
 * Download remote File and save as a Media.
 *
 * @XlsxCell(
 *   id = "save_url_as_image_media",
 *   name = @Translation("Save URL As Media (image)"),
 *   description = @Translation("Download remote file, save it as media entity and attach to an entity."),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 */
class SaveUrlAsImageMedia extends XlsxCellBase {

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
          $media = Media::create([
            'bundle' => 'image',
            'uid' => \Drupal::currentUser()->id(),
            'field_media_image' => [
              'target_id' => $file->id(),
            ],
          ]);
          $media->setName($filename)
            ->setPublished(TRUE)
            ->save();
          // Keep track of imported files. This will prevent file duplication in the future.
          // We checking file hash to make sure it is unique. Not the best apporach as a generic solution
          // but a good start for a custom plugin.
          $mapping = XlsxEntityMapping::create([]);
          $mapping->setMapEntityType('media', 'image');
          $mapping->setMapEntityId($media->id());
          $mapping->setMapHash($hash);
          $mapping->save();
          return ['target_id' => $media->id()];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function export($entity, $field_name, $value) {
    if ($entity->hasField($field_name) && !empty($value[0]['target_id'])) {
      if ($media = Media::load($value[0]['target_id'])) {
        if ($media_field = $media->get('field_media_image')->first()->getValue()) {
          if ($file =  File::load($media_field['target_id'])) {
            return Url::fromUri($file->createFileUrl(FALSE), ['absolute' => TRUE])->toString();
          }
        }
      }
    }
  }

}
