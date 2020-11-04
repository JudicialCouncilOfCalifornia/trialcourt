<?php

namespace Drupal\jcc_tc_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\redirect\Entity\Redirect;

/**
 * Provides url_shortener plugin.
 *
 * Returns a shortened URL redirect.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: url_shortener
 *     source: url
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "url_shortener"
 * )
 */
class UrlShortener extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    if (empty($value) || strlen($value) < 100) {
      return $value;
    }

    $source = '/r/' . user_password(20);
    $redirect = Redirect::create();
    $redirect->setStatusCode('301');
    $redirect->setLanguage('und');
    $redirect->setSource($source);
    $redirect->setRedirect($value);
    $redirect->save();

    return $source;
  }

}
