<?php

/**
 * @file
 * Rector configuration for custom modules and themes.
 */

declare(strict_types=1);

use DrupalFinder\DrupalFinderComposerRuntime;
use DrupalRector\Rector\PHPUnit\PhpUnitAddRunTestsInSeparateProcessesAttributeRector;
use DrupalRector\Rector\PHPUnit\PhpUnitTestAnnotationToAttributeRector;
use DrupalRector\Services\DrupalRectorSettings;
use DrupalRector\Set\Drupal11SetList;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
  // Only scan custom code.
  $rectorConfig->paths([
    __DIR__ . '/web/modules/custom',
    __DIR__ . '/web/themes/custom',
  ]);

  // Drupal 11 deprecation rules (11.0 to 11.4). APIs deprecated in Drupal 8,
  // 9 and 10 no longer exist in Drupal 11 core, so their rules are not needed.
  $rectorConfig->sets([
    Drupal11SetList::DRUPAL_11,
  ]);

  // Custom code only runs on Drupal 11, so replace deprecated calls directly
  // instead of wrapping them in DeprecationHelper::backwardsCompatibleCall().
  $rectorConfig->singleton(DrupalRectorSettings::class, fn () => (new DrupalRectorSettings())
    ->disableBackwardCompatibility());

  // Load phpstan-drupal so the Drupal rules get better type inference.
  $rectorConfig->phpstanConfigs([
    __DIR__ . '/vendor/mglaman/phpstan-drupal/extension.neon',
  ]);

  $drupalRoot = (new DrupalFinderComposerRuntime())->getDrupalRoot();
  $rectorConfig->autoloadPaths([
    $drupalRoot . '/core',
    $drupalRoot . '/modules',
    $drupalRoot . '/profiles',
    $drupalRoot . '/themes',
  ]);

  $rectorConfig->skip([
    '*/node_modules/*',
    '*/upgrade_status/tests/modules/*',
    // PHPUnit is not installed (no drupal/core-dev), so its attribute classes
    // do not exist and phpstan reports them. Keep the annotations for now.
    PhpUnitAddRunTestsInSeparateProcessesAttributeRector::class,
    PhpUnitTestAnnotationToAttributeRector::class,
  ]);
  $rectorConfig->fileExtensions([
    'php',
    'module',
    'theme',
    'install',
    'profile',
    'inc',
    'engine',
  ]);
  $rectorConfig->importNames(TRUE, FALSE);
  $rectorConfig->importShortClasses(FALSE);
};
