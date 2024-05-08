<?php

namespace Drupal\jcc_custom\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Creates media path from a provided mid.
 *
 * @MigrateProcessPlugin(
 *   id = "jcc_media_path"
 * )
 *
 * To do custom value transformations use the following.
 * Use in combination with the link_uri for URI scheme.
 *
 * @code
 * field_link:
 *   plugin: jcc_media_path
 *   source: string
 * @endcode
 */
class JccMediaPath extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($value && is_numeric($value)) {
      $uri = '/media/' . $value;
      return $uri;
    }
    else {
      throw new MigrateException(sprintf('"%s" is not a valid media id.', $value));
    }
  }

}
