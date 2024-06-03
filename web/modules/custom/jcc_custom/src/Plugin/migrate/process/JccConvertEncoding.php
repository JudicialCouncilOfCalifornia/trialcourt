<?php

namespace Drupal\jcc_custom\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Converts encoded characters for Web compatibility.
 *
 * Refer to https://www.php.net/manual/en/function.mb-convert-encoding.php.
 *
 * @MigrateProcessPlugin(
 *   id = "jcc_convert_encoding"
 * )
 *
 * To do custom value transformations use the following.
 *
 * @code
 * field_link:
 *   plugin: jcc_convert_encoding
 *   source: string
 *   from: 'original' (e.g. 'Windows-1252' or 'Windows-1252, ISO 8859-1,...')
 *   to: 'conversion' (e.g. 'UTF-8')
 * @endcode
 */
class JccConvertEncoding extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $from = $this->configuration['from'];
    $to = $this->configuration['to'];

    if ($value && $from) {
      $value = mb_convert_encoding($value, $to, $from);
    }
    return $value;
  }

}
