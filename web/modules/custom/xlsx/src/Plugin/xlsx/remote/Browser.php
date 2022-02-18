<?php

namespace Drupal\xlsx\Plugin\xlsx\remote;

use Drupal\xlsx\Plugin\XlsxRemoteBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *  Download file in browser.
 *
 * @XlsxRemote(
 *   id = "browser",
 *   name = @Translation("Download Now"),
 *   description = @Translation("Download file in browser.")
 * )
 */
class Browser extends XlsxRemoteBase {

  public function process($contents, $filename, $filesize, $extension, $content_type) {
    header("Content-Type: {$content_type}");
    header("Content-Disposition: attachment; filename={$filename}{$extension}; filename*=UTF-8''{$filename}{$extension};");
    header("Cache-Control: max-age=0");
    print $contents;
    exit();
  }

}
