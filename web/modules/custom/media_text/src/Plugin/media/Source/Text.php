<?php

namespace Drupal\media_text\Plugin\media\Source;

use Drupal\media\MediaSourceBase;
use Drupal\media\MediaInterface;

/**
 * Provides a media source plugin for Media Text resources.
 *
 * @MediaSource(
 *   id = "text",
 *   label = @Translation("Text"),
 *   allowed_field_types = {"text_long"},
 *   thumbnail_alt_metadata_attribute = "alt",
 *   default_thumbnail_filename = "generic.png",
 *   forms = {
 *     "media_library_add" = "\Drupal\media_text\Form\CreateForm"
 *   }
 * )
 */
class Text extends MediaSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      'name' => $this->t('Name'),
      'text' => $this->t('Text field'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    switch ($attribute_name) {
      case 'name':
      case 'default_name':
        return 'Text';

      case 'text':
        return $media->get($this->configuration['source_field'])->getValue();

      default:
        return parent::getMetadata($media, $attribute_name);
    }
  }

}
