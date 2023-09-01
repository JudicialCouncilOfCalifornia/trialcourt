<?php

/**
 * @file
 * Contains API hooks for jcc_elevated_custom.
 */

/**
 * Implements hook_jcc_elevated_settings_alter().
 *
 * This allows to alter/add/edit setting values from the custom site settings
 * that are returned in:
 *  - jcc_elevated_get_custom_settings();
 *  - jcc_elevated_get_custom_setting($setting_name);
 *  - Twig function {{ jcc_elevated_setting($setting_name) }}
 */
function hook_jcc_elevated_settings_alter(&$settings) {
  $settings['jcc_elevated.test'] = 'This is a test value';
}
