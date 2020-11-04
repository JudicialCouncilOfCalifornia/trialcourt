<?php

namespace Drupal\jcc_tc_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\redirect\Entity\Redirect;
use Drupal\Core\Database\Query\Condition;

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

    // Check if a redirect already exists.
    $value = trim($value);
    $database = \Drupal::database();
    $query = $database->select('redirect');
    $query->addField('redirect', 'redirect_source__path');
    $query_or = new Condition('OR');
    $query_or->condition('redirect_redirect__uri', $value, '=');
    $query->condition($query_or);
    $source_path = $query->execute()->fetchCol();
    if ($source_path) {
      foreach ($source_path as $s) {
        return $s;
      }
    }

    // Otherwise create a new one.
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
