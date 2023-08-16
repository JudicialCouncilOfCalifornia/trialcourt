<?php

namespace Drupal\jcc_elevated_custom;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * An extension to return a jcc elevated custom setting directly in twig.
 */
class JccElevatedCustomSettingsTwigExtension extends AbstractExtension {

  use StringTranslationTrait;

  /**
   * Gets a unique identifier for this Twig extension.
   *
   * @return string
   *   A unique identifier for this Twig extension.
   */
  public function getName() {
    return 'jcc_elevated_custom.extension';
  }

  /**
   * Get a jcc elevated custom site setting.
   */
  public function getFunctions() {
    return [
      new TwigFunction('jcc_elevated_setting', [
        $this, 'jccElevatedCustomGetSetting',
      ]),
      new TwigFunction('jcc_elevated_site_name', [
        $this, 'jccElevatedCustomGetSiteName',
      ]),
    ];
  }

  /**
   * Return a setting value.
   *
   * @param string $setting_name
   *   The setting name, usually starts with "jcc_elevated...".
   *
   * @return mixed
   *   Returns a string or array of string values.
   */
  public static function jccElevatedCustomGetSetting($setting_name) {
    return jcc_elevated_get_custom_setting($setting_name);
  }

  /**
   * Return the site name. Useful for "if site is" comparisons.
   */
  public static function jccElevatedCustomGetSiteName() {
    return jcc_elevated_get_site_name();
  }

}
