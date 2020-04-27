<?php

namespace Drupal\jcc_media_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Url;
use Drupal\media\MediaInterface;
use Drupal\file\Entity\File;

/**
 * Plugin implementation of the 'media_file_url' formatter.
 *
 * @FieldFormatter(
 *   id = "media_file_url",
 *   label = @Translation("Media File URL"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MediaFileUrl extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $media_items = $this->getEntitiesToView($items, $langcode);

    if (empty($media_items)) {
      return $elements;
    }

    /** @var \Drupal\media\MediaInterface[] $media_items */
    foreach ($media_items as $delta => $media) {

      $value = "";

      // Only handle media objects.
      if ($media instanceof MediaInterface) {

        // Get the value from the source field.
        try {
          $value = $media->getSource()->getSourceFieldValue($media);
        }
        catch(\Error $e) {
          // Handle corrupted media
        }

        $label = '';

        // If this returns a numeric value, it's a file entity's ID.
        if (is_numeric($value)) {
          $file = File::load($value);
          if (!empty($file)) {
            $uri = $file->getFileUri();
            if (!empty($uri)) {
              $value = file_create_url($uri);
            }
            $label = $file->getFilename();
          }
        }

        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $media->getName() ?: $label,
          '#url' => $value ? Url::fromUri($value) : '',
        ];

      }
    }
    return $elements;
  }
}
