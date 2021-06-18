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

/**
 * Additional twig extensions.
 */
class TwigExtension extends \Twig_Extension {
  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('remove_empty', [$this, 'removeEmpty']),
      new \Twig_SimpleFilter('clean_unique_id', [$this, 'uniqueId']),
      new \Twig_SimpleFilter('i18n_format_date', [$this, 'formatDate'], ['needs_environment' => TRUE]),
      new \Twig_SimpleFilter('remove_html_comments', [$this, 'removeHtmlComments']),
      new \Twig_SimpleFilter('unescape', [$this, 'unescape']),
      new \Twig_SimpleFilter('auto_convert_urls', [$this, 'autoConvertUrls']),
      new \Twig_SimpleFilter('image_style', [$this, 'imageStyle']),
      new \Twig_SimpleFilter('view', [$this, 'view']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('term_field_from_id', [$this, 'termFieldFromId'], ['is_safe' => ['html']]),
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
   * Render a custom date format with Twig.
   *
   * Use the internal helper "format_date" to render the date
   * using the current language for texts.
   *
   * @param \Drupal\Core\Template\TwigEnvironment $env
   *   A Twig_Environment instance.
   * @param int|string|DateTime $date
   *   A string, integer timestamp or DateTime object to convert.
   * @param string $type
   *   (optional) The format to use, one of:
   *   - One of the built-in formats: 'short', 'medium',
   *     'long', 'html_datetime', 'html_date', 'html_time',
   *     'html_yearless_date', 'html_week', 'html_month', 'html_year'.
   *   - The name of a date type defined by a date format config entity.
   *   - The machine name of an administrator-defined date format.
   *   - 'custom', to use $format.
   *   Defaults to 'medium'.
   * @param string $format
   *   (optional) If $type is 'custom', a PHP date format string suitable for
   *   input to date(). Use a backslash to escape ordinary text, so it does not
   *   get interpreted as date format characters.
   * @param string|null $timezone
   *   (optional) Time zone identifier, as described at
   *   http://php.net/manual/timezones.php Defaults to the time zone used to
   *   display the page.
   * @param string|null $langcode
   *   (optional) Language code to translate to. NULL (default) means to use
   *   the content language for the page.
   *
   * @return string|null
   *   A translated date string in the requested format. Since the format may
   *   contain user input, this value should be escaped when output.
   */
  public function formatDate(TwigEnvironment $env, $date, $type = 'medium', $format = '', $timezone = NULL, $langcode = NULL) {
    // Use user's timezone so date/time is rendered without timezone adjustment.
    $account = \Drupal::currentUser();
    $timezone = $account->getTimeZone() ?? 'America/Los_Angeles';

    $date = twig_date_converter($env, $date);
    if (empty($langcode)) {
      $langcode = $this->getLanguageManager()
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();
    }

    if ($date instanceof \DateTime) {
      return $this->getDateFormatter()->format($date->getTimestamp(), $type, $format, $timezone, $langcode);
    }
    return NULL;
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

    return file_url_transform_relative($image_style->buildUrl($path));
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
