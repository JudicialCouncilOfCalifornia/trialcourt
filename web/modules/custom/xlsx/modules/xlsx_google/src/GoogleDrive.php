<?php

namespace Drupal\xlsx_google;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\xlsx_google\Form\GoogleSheetsForm;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Google_Service_Sheets;
use Google_Service_Drive_Permission;

/**
 * Google Drive service.
 *
 * @ingroup xlsx
 */
class GoogleDrive {

  /**
   * The config service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a \Drupal\xlsx\GoogleDrive object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory, FileSystemInterface $file_system) {
    $this->configFactory = $configFactory;
    $this->fileSystem = $file_system;
  }

  /**
   * Upload file to Google Drive.
   */
  public function uploadFile($data, $filename, $dir, $mime) {
    $client = $this->getClient();
    $service = new Google_Service_Drive($client);
    $file = new Google_Service_Drive_DriveFile();
    $file->setName($filename);
    if (!empty($dir)) {
      $file->setParents([$dir]);
    }
    $info = $service->files->create($file, [
      'data' => $data,
      'mimeType' => $mime,
      'uploadType' => 'resumable'
    ]);
    if ($info->id) {
      $this->setPermission($service, $info->id);
      return $info;
    }
  }

  /**
   * Build Google client.
   */
  protected function getClient() {
    $file = File::load(\Drupal::state()->get('xlsx_service_config_file_id'));
    $credentials_path = $this->fileSystem->realpath($file->getFileUri());
    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $credentials_path);
    try { 
      // Create and configure a new client object.    
      $client = new Google_Client();
      $client->useApplicationDefaultCredentials();
      $client->setApplicationName('Xlsx Drupal module');
      $client->setScopes([
        Google_Service_Drive::DRIVE,
        Google_Service_Sheets::SPREADSHEETS_READONLY
      ]);
      return $client;
    }
    catch (Exception $e) {
      print "An error occurred: " . $e->getMessage();
    }
  }

  /**
   * Insert a new permission.
   *
   * @param Google_Service_Drive $service Drive API service instance.
   * @param String $fileId ID of the file to insert permission for.
   * @param String $emails User or group e-mail address, domain name or NULL for
                         "default" type.
   * @param String $type The value "user", "group", "domain" or "default".
   * @param String $role The value "owner", "writer" or "reader".
   * @return Google_Servie_Drive_Permission The inserted permission. NULL is
   *     returned if an API error occurred.
   */
  public function setPermission($service, $fileId, $emails = '', $type = 'anyone', $role = 'writer') {
    $newPermission = new Google_Service_Drive_Permission();
    if (!empty($emails)) {
      $newPermission->setEmailAddress($emails);
    }
    $newPermission->setType($type);
    $newPermission->setRole($role);
    try {
      return $service->permissions->create($fileId, $newPermission);
    } catch (Exception $e) {
      print "An error occurred: " . $e->getMessage();
    }
    return NULL;
  }

}
