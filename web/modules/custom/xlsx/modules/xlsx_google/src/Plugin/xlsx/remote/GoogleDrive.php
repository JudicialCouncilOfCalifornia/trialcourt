<?php

namespace Drupal\xlsx_google\Plugin\xlsx\remote;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\xlsx\Plugin\XlsxRemoteBase;

/**
 *  Upload to Google Drive.
 *
 * @XlsxRemote(
 *   id = "google_drive",
 *   name = @Translation("Google Drive"),
 *   description = @Translation("Upload to Google Drive.")
 * )
 */
class GoogleDrive extends XlsxRemoteBase {
  use MessengerTrait;

  public function process($contents, $filename, $filesize, $extension, $content_type) {
  	$google_drive = \Drupal::service('xlsx_google.google_drive');
    if ($item = $google_drive->uploadFile($contents, $filename, '', $content_type)) {
      $this->messenger()->addStatus('Data succesfully exported to Google Drive');
    }
  }

}
