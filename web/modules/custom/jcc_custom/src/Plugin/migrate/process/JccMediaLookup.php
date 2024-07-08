<?php

namespace Drupal\jcc_custom\Plugin\migrate\process;

use Drupal\media\Entity\Media;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Returns media information from the provided mid.
 *
 * @MigrateProcessPlugin(
 *   id = "jcc_media_lookup"
 * )
 *
 * To do custom value transformations use the following.
 * Use in combination with the link_uri for URI scheme.
 *
 * @code
 * field_link:
 *   plugin: jcc_media_lookup
 *   source: array
 * @endcode
 */
class JccMediaLookup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($value && is_numeric($value)) {
      $link = [];
      $media = Media::load($value);
      if ($media) {
        $link = [
          'uri' => 'entity:media/' . $value,
          'description' => $media->get('field_media_text')->value,
        ];
      }
      return $link;
    }
    else {
      throw new MigrateException(sprintf('"%s" is not a valid media id.', $value));
    }
  }

}
