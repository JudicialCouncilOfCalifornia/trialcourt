<?php

namespace Drupal\jcc_custom\Plugin\media\Source;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;

/**
 * Media source wrapping around link media entity fields.
 *
 * @see \Drupal\file\FileInterface
 *
 * @MediaSource(
 *   id = "link",
 *   label = @Translation("Link"),
 *   description = @Translation("Use links as reusable media."),
 *   allowed_field_types = {"link"},
 *   default_thumbnail_filename = "no-thumbnail.png",
 * )
 */
class LinkMediaEntity extends MediaSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    return parent::createSourceField($type)->set('label', 'Link');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display) {
    $display->setComponent($this->getSourceFieldDefinition($type)->getName(), [
      'type' => 'link',
      'label' => 'hidden',
    ]);
  }

}
