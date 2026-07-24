<?php

namespace Drupal\jcc_twig\Twig;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Additional twig extensions.
 */
class TwigExtension extends AbstractExtension {
  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('remove_empty', [$this, 'removeEmpty']),
      new TwigFilter('clean_unique_id', [$this, 'uniqueId']),
      new TwigFilter('remove_html_comments', [$this, 'removeHtmlComments']),
      new TwigFilter('unescape', [$this, 'unescape']),
      new TwigFilter('auto_convert_urls', [$this, 'autoConvertUrls']),
      new TwigFilter('image_style', [$this, 'imageStyle']),
      new TwigFilter('view', [$this, 'view']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction('term_field_from_id', [$this, 'termFieldFromId'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * Remove empty items from an array.
   */
  public function removeEmpty($array) {
    if (is_array($array)) {
      $array = array_filter($array);
    }

    return $array;
  }

  /**
   * Prepares a string for use as a valid HTML ID and guarantees uniqueness.
   *
   * See Html::getUniqueId()
   */
  public function uniqueId($id) {
    return Html::getUniqueId($id);
  }

  /**
   * Removes html comments from string.
   */
  public function removeHtmlComments($string) {
    $output = preg_replace('/<!--(.|\s)*?-->/', '', $string);
    return $output;
  }

  /**
   * Gets a field value from a taxonomy term.
   */
  public function termFieldFromId($id, $field_name) {
    if (!is_numeric($id)) {
      return '';
    }

    $field = '';
    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($id);

    if (!empty($term)) {
      $field = $term->{$field_name}->value;
    }

    return $field;
  }

  /**
   * Decodes all HTML entities including numerical ones to regular UTF-8 bytes.
   */
  public function unescape($value) {
    return Html::decodeEntities($value);
  }

  /**
   * Returns the URL of this image derivative for an original image path or URI.
   *
   * @param string $path
   *   The path or URI to the original image.
   * @param string $style
   *   The image style.
   *
   * @return string|null
   *   The absolute URL where a style image can be downloaded, suitable for use
   *   in an <img> tag. Requesting the URL will cause the image to be created.
   */
  public function imageStyle($path, $style) {
    // @phpcs:ignore DrupalPractice.Objects.GlobalClass.GlobalClass
    if (!$image_style = ImageStyle::load($style)) {
      trigger_error(sprintf('Could not load image style %s.', $style));
      return;
    }

    if (!$image_style->supportsUri($path)) {
      trigger_error(sprintf('Could not apply image style %s.', $style));
      return;
    }

    return \Drupal::service('file_url_generator')->transformRelative($image_style->buildUrl($path));
  }

  /**
   * Returns a render array for entity, field list or field item.
   *
   * @param mixed $object
   *   The object to build a render array from.
   * @param string|array $display_options
   *   Can be either the name of a view mode, or an array of display settings.
   * @param string $langcode
   *   (optional) For which language the entity should be rendered, defaults to
   *   the current content language.
   * @param bool $check_access
   *   (optional) Indicates that access check for an entity is required.
   *
   * @return array
   *   A render array to represent the object.
   */
  public function view($object, $display_options = 'default', $langcode = NULL, $check_access = TRUE) {
    if ($object instanceof FieldItemListInterface || $object instanceof FieldItemInterface) {
      return $object->view($display_options);
    }
    elseif ($object instanceof EntityInterface) {
      $access = $check_access ? $object->access('view', NULL, TRUE) : AccessResult::allowed();
      if ($access->isAllowed()) {
        $build = \Drupal::entityTypeManager()
          ->getViewBuilder($object->getEntityTypeId())
          ->view($object, $display_options, $langcode);
        CacheableMetadata::createFromRenderArray($build)
          ->merge(CacheableMetadata::createFromObject($object))
          ->merge(CacheableMetadata::createFromObject($access))
          ->applyTo($build);
        return $build;
      }
    }

    return [];
  }

  /**
   * Finds different occurrences of urls or email addresses in a string.
   */
  public function autoConvertUrls($string) {
    $pattern = '/(href="|src=")?([-a-zA-Zа-яёА-ЯЁ0-9@:%_\+.~#?&\*\/\/=]{2,256}\.[a-zа-яё]{2,4}\b(\/?[-\p{L}0-9@:%_\+.~#?&\*\/\/=\(\),;]*)?)/u';
    $stringFiltered = preg_replace_callback($pattern, [$this, 'callbackReplace'], $string);

    return $stringFiltered;
  }

  /**
   * Replace text from autoConvertUrls.
   */
  public function callbackReplace($matches) {
    if ($matches[1] !== '') {
      // Don't modify existing <a href="">links</a> and <img src="">.
      return $matches[0];
    }

    $url = $matches[2];
    $urlWithPrefix = $matches[2];

    if (strpos($url, '@') !== FALSE) {
      $urlWithPrefix = 'mailto:' . $url;
    }
    elseif (strpos($url, 'https://') === 0) {
      $urlWithPrefix = $url;
    }
    elseif (strpos($url, 'http://') !== 0) {
      $urlWithPrefix = 'http://' . $url;
    }

    // Ignore tailing special characters.
    if (preg_match("/^(.*)(\.|\,|\?)$/", $urlWithPrefix, $matches)) {
      $urlWithPrefix = $matches[1];
      $url = substr($url, 0, -1);
      $punctuation = $matches[2];
    }
    else {
      $punctuation = '';
    }

    return '<a href="' . $urlWithPrefix . '">' . $url . '</a>' . $punctuation;
  }

  /**
   * Returns the language manager service.
   */
  protected function getLanguageManager() {
    return $this->container->get('language_manager');
  }

  /**
   * Provides a service to handle various date related functionality.
   */
  protected function getDateFormatter() {
    return $this->container->get('date.formatter');
  }

}
