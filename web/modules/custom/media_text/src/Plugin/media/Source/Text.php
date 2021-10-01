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
 *   description = @Translation("Embed text content"),
 *   allowed_field_types = {"text_long"},
 *   thumbnail_alt_metadata_attribute = "alt",
 *   default_thumbnail_filename = "generic.png",
 *   forms = {
 *     "media_library_add" = "\Drupal\media_text\Form\TextForm"
 *   }
 * )
 */
class Text extends MediaSourceBase {

  /**
   * Key for "Name" metadata attribute.
   *
   * @var string
   */
  const METADATA_ATTRIBUTE_NAME = 'name';

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      static::METADATA_ATTRIBUTE_NAME => $this->t('Name'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    $text = $media->get($this->configuration['source_field'])->getValue();

    switch ($attribute_name) {
      // The first bit of text as the default name.
      case static::METADATA_ATTRIBUTE_NAME:
      case 'default_name':
        return isset($text) ? substr(strip_tags($text[0]['value']), 0, 40) : $this->t('Text');

      default:
        return parent::getMetadata($media, $attribute_name);
    }
  }

}
