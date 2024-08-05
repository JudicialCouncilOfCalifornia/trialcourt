<?php

namespace Drupal\jcc_elevated_rfp_solicitations\Services;

use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;

/**
 * Class MediaReplaceFileLink.
 *
 * Search and replace file links with equivalent media entity links if
 * available.
 */
class JccSolicitationMediaReplaceFileLinkService {

  /**
   * Constructs a new CustomService object.
   */
  public function __construct($entity_type_manager, $file_usage, $pathauto_alias_cleaner, LoggerChannelFactoryInterface $factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUsage = $file_usage;
    $this->pathAutoAliasCleaner = $pathauto_alias_cleaner;
    $this->loggerFactory = $factory;
  }

  /**
   * Replace direct file links with media entity equivalent if available.
   *
   * @param string $value
   *   The html in which to replace links.
   *
   * @return string
   *   The updated string.
   */
  public function replace($value) {

    $mb_converted_value = mb_convert_encoding($value, 'HTML-ENTITIES', 'UTF-8');

    $dom = new \DomDocument();
    $dom->loadHTML($mb_converted_value);

    foreach ($dom->getElementsByTagName('a') as $item) {
      // The original link string that will be replaced.
      $original = $dom->saveHTML($item);
      // The original link text for reconstructing link.
      $text = $item->nodeValue;
      // The url for the link to determine file name.
      $href = $item->getAttribute('href');
      $path = parse_url($href, PHP_URL_PATH);
      $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
      // @todo Get this list from config.
      $file_types = explode(', ', 'pdf, zip, doc, docx, xls, xlsx, ppt, pptx');

      if (in_array($extension, $file_types)) {
        $value = $this->cleanString($value);
        $text = $this->cleanString($text);
        $replace = $this->mediaLink($path, $text, $original, $extension);
        $replace = $this->cleanString($replace);
        $original = $this->cleanString($original);
        $value = str_replace($original, $replace, $value);

        if (!str_contains($value, $replace)) {
          $message = t('A link failed to be replaced: @original', ['@original' => $original]);
          $this->loggerFactory->get('jcc_solicitation_migration')->notice($message);
        }
      }
    }

    return $value;
  }

  /**
   * Generate a media link from path.
   *
   * @param string $path
   *   The direct file path.
   * @param string $text
   *   The link text.
   * @param string $link
   *   The original link to return if no media found.
   * @param string $extension
   *   The original extension.
   *
   * @return string
   *   The update link.
   */
  public function mediaLink($path, $text, $link, $extension): string {
    $media = $this->getMedia($path, $extension);

    if ($media) {
      $options['attributes'] = [
        'data-entity-substitution' => 'media',
        'data-entity-type' => 'media',
        'data-entity-uuid' => $media->uuid(),
        'target' => '_blank',
      ];
      $url = Url::fromUri('base:media/' . $media->id());
      $url->setOptions($options);
      $link = Link::fromTextAndUrl($text, $url);
      $link = $link->toString();
      $link = $link->getGeneratedLink();
    }
    else {
      // Add forward slash to relative path if needed.
      if (stripos($path, 'http') === FALSE) {
        if (strpos($path, '/') !== 0) {
          $new_path = "/$path";
          $link = str_replace($path, $new_path, $link);
        }
      }
    }

    return $link;
  }

  /**
   * Get media entity by file name.
   *
   * @param string $path
   *   The path with the file name to search for.
   * @param string $extension
   *   The original extension.
   *
   * @return object|null
   *   The media object or null.
   */
  public function getMedia($path, $extension): ?object {
    $media = NULL;
    $filename = $this->getFileNameFromPath($path, $extension);

    if ($filename) {
      $file = $this->entityTypeManager
        ->getStorage('file')
        ->loadByProperties(['filename' => $filename]);

      // We only return the first media item that references this file.
      // There should only be one anyway.
      if (!empty($file)) {
        $file = array_shift($file);
        $usage = $this->fileUsage->listUsage($file);
        if (!empty($usage['file']['media'])) {
          $media_id_keys = array_keys($usage['file']['media']);
          $media_id = array_shift($media_id_keys);
          $media = is_numeric($media_id) ? $this->entityTypeManager->getStorage('media')->load($media_id) : NULL;
        }
      }
    }

    return $media;
  }

  /**
   * Get file name from path.
   *
   * @param string $path
   *   The path with the file name to search for.
   * @param string $extension
   *   The original extension.
   *
   * @return string|bool
   *   The string or false.
   */
  public function getFileNameFromPath($path, $extension): bool|string {
    if (empty(($path))) {
      return FALSE;
    }

    if (empty(($extension))) {
      return FALSE;
    }

    // We need to remove the extension from the path, for further processing.
    $file_types = explode(', ', '.pdf, .zip, .docx, .docx, .xls, .pptx, .ppt');

    // We remove file type at end.
    $filename = str_replace($file_types, "", $path);

    // We remove prefix path item.
    $filename = str_replace("/documents/", "", $filename);

    // Pass through the pathauto cleaner. The file/media importer cleans the
    // names, so it needs this to actually have a chance to match the names.
    $filename = $this->pathAutoAliasCleaner->cleanString($filename);

    // We rebuild the base filename with the extension.
    return "$filename.$extension";
  }

  /**
   * Helper function to help try to clean up a string.
   *
   * @param string $value
   *   String to clean of html entities.
   *
   * @return string
   *   Return cleaned string.
   */
  public function cleanString($value): string {

    $string = htmlentities($value, NULL, 'utf-8');

    $value = str_replace("&nbsp;", " ", $string);
    $value = str_replace('&amp;', "&", $value);
    $value = str_replace('&ndash;', "–", $value);

    return html_entity_decode($value);
  }

}
