<?php

namespace Drupal\jcc_tc_migration\Services;

use Drupal\Core\Url;

/**
 * Class MediaReplaceFileLink.
 *
 * Search and replace file links with equivelant media entity links if
 * available.
 */
class MediaReplaceFileLink {

  /**
   * Constructs a new CustomService object.
   */
  public function __construct($entity_type_manager, $file_usage) {
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUsage = $file_usage;
  }

  /**
   * Replace direct file links with media entity equivalant if available.
   *
   * @param string $value
   *   The html in which to replace links.
   *
   * @return string
   *   The updated string.
   */
  public function replace($value) {
    $dom = new \DomDocument();
    $dom->loadHTML($value);

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
        $url = $this->mediaLink($path, $text, $original);
        if ($url) {
          foreach ($url->getOptions()['attributes'] as $attribute => $attr_value) {
            $item->setAttribute($attribute, $attr_value);
            $item->setAttribute('href', $url->toString());
          }
          $value = $dom->saveHtml();
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
   *
   * @return string
   *   The update link.
   */
  public function mediaLink($path, $text, $link) {
    $media = $this->getMedia($path);

    if ($media) {
      $options['attributes'] = [
        'data-entity-substitution' => 'media',
        'data-entity-type' => 'media',
        'data-entity-uuid' => $media->uuid(),
        'media_library' => 'Media Library',
        'target' => '_blank',
      ];
      $url = Url::fromUri('internal:/media/' . $media->id());
      $url->setOptions($options);

      return $url;
    }

    return FALSE;
  }

  /**
   * Get media entity by file name.
   *
   * @param string $path
   *   The path with the file name to search for.
   *
   * @return object|null
   *   The media object or null.
   */
  public function getMedia($path) {
    $usage = [];
    $files = $this->entityTypeManager
      ->getStorage('file')
      ->loadByProperties(['filename' => pathinfo($path, PATHINFO_BASENAME)]);

    foreach ($files as $file) {
      $usage += $this->fileUsage->listUsage($file);
    }

    if (!empty($usage['file']['media'])) {
      $ids = array_keys($usage['file']['media']);
      // No way to know which specific media id to use so use first.
      // It will have the required file reference in any case.
      $media_id = array_shift($ids);
      $media = $this->entityTypeManager->getStorage('media')->load($media_id);
    }

    return !empty($media) ? $media : NULL;
  }

}
