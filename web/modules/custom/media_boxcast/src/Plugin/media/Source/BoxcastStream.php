<?php

namespace Drupal\media_boxcast\Plugin\media\Source;

use Drupal\media\MediaSourceBase;

/**
 * External boxcast stream media source.
 *
 * @MediaSource(
 *   id = "boxcast_stream",
 *   label = @Translation("Boxcast Stream"),
 *   description = @Translation("Embed a Boxcast Stream."),
 *   allowed_field_types = {"boxcast_content"},
 *   thumbnail_alt_metadata_attribute = "alt",
 *   default_thumbnail_filename = "video.png"
 * )
 */
class BoxcastStream extends MediaSourceBase {

  /**
   * {@inheritDoc}
   */
  public function getMetadataAttributes() {
    return [];
  }

}
