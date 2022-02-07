<?php

namespace Drupal\xlsx\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface XlsxRemote plugins.
 */
interface XlsxRemoteInterface extends PluginInspectionInterface {

  /**
   * Return the name of the XLSX remote plugin.
   *
   * @return string
   */
  public function getName();

  /**
   * Return description for the XLSX remote plugin.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Get class to check availability.
   */
  public function classExists();

  /**
   * Process data.
   */
  public function process($contents, $filename, $filesize, $extension, $content_type);

}
