<?php

namespace Drupal\jcc_pdf_upload_validation_checker\Render;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;

/**
 * Replaces blocked embedded media links in rendered text.
 */
final class BlockedMediaLinkPostRender implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['replaceBlockedMediaLinks'];
  }

  /**
   * Replaces blocked media links with placeholder text.
   *
   * @param string $html
   *   The rendered HTML.
   * @param array $element
   *   The render element.
   *
   * @return string
   *   The altered HTML.
   */
  public static function replaceBlockedMediaLinks(string $html, array $element): string {
    if (stripos($html, 'data-entity-type="media"') === FALSE) {
      return $html;
    }

    $dom = new \DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(TRUE);

    $dom->loadHTML(
      '<?xml encoding="utf-8" ?><div id="jcc-root">' . $html . '</div>',
      LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );

    $xpath = new \DOMXPath($dom);
    $links = $xpath->query('//a[@data-entity-type="media" and @data-entity-substitution="media"]');

    if (!$links || $links->length === 0) {
      libxml_clear_errors();
      return $html;
    }

    foreach ($links as $link) {
      /** @var \DOMElement $link */
      $uuid = $link->getAttribute('data-entity-uuid');
      if (!$uuid) {
        continue;
      }

      $media_list = \Drupal::entityTypeManager()
        ->getStorage('media')
        ->loadByProperties(['uuid' => $uuid]);

      if (!$media_list) {
        continue;
      }

      $media = reset($media_list);
      if (!$media instanceof MediaInterface) {
        continue;
      }

      if (self::mediaHasBlockingPdf($media)) {
        $replacement = $dom->createElement('span', (string) t('Document unavailable'));
        $replacement->setAttribute('class', 'jcc-pdf-unavailable');
        $link->parentNode->replaceChild($replacement, $link);
      }
    }

    $root = $dom->getElementById('jcc-root');
    $output = '';

    if ($root) {
      foreach ($root->childNodes as $child) {
        $output .= $dom->saveHTML($child);
      }
    }

    libxml_clear_errors();
    return $output ?: $html;
  }

  /**
   * Returns TRUE if the media contains a blocking PDF.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return bool
   *   TRUE if one referenced PDF is pending or failed.
   */
  protected static function mediaHasBlockingPdf(MediaInterface $media): bool {
    foreach (self::mediaFileFields() as $field_name) {
      if (!$media->hasField($field_name) || $media->get($field_name)->isEmpty()) {
        continue;
      }

      foreach ($media->get($field_name) as $item) {
        $file = $item->entity;
        if (!$file instanceof FileInterface) {
          continue;
        }

        if (self::fileIsBlockingPdf($file)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Returns TRUE if the file is a blocking PDF.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity.
   *
   * @return bool
   *   TRUE if the file is pending or failed.
   */
  protected static function fileIsBlockingPdf(FileInterface $file): bool {
    if ($file->getMimeType() !== 'application/pdf') {
      return FALSE;
    }

    $status = '';
    if ($file->hasField('field_pdf_audit_status')) {
      $status = (string) $file->get('field_pdf_audit_status')->value;
    }

    return in_array($status, ['pending', 'fail'], TRUE);
  }

  /**
   * Returns the media file fields handled by the module.
   *
   * @return string[]
   *   The media file field names.
   */
  protected static function mediaFileFields(): array {
    return [
      'field_media_file',
      'field_media_file_farsi',
      'field_media_file_arabic',
      'field_media_file_cambodian',
      'field_media_file_chinese_simple',
      'field_media_file_chinese',
      'field_east_armenian_file',
      'field_media_file_hmong',
      'field_media_file_korean',
      'field_media_file_multiple',
      'field_media_file_punjabi',
      'field_media_file_russian',
      'field_media_file_spanish',
      'field_media_file_tagalog',
      'field_media_file_vietnamese',
    ];
  }

}
