<?php

namespace DrupalProject\composer;

use Composer\Script\Event;
use Composer\Semver\Comparator;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

/**
 * Script Handler Class for composer.json.
 */
class ScriptHandler {

  /**
   * Create Drupal files.
   *
   * @param \Composer\Script\Event $event
   *   Composer Event.
   */
  public static function createRequiredFiles(Event $event) {
    $fs = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();

    $dirs = [
      'modules',
      'profiles',
      'themes',
    ];

    // Required for unit testing.
    foreach ($dirs as $dir) {
      if (!$fs->exists($drupalRoot . '/' . $dir)) {
        $fs->mkdir($drupalRoot . '/' . $dir);
        $fs->touch($drupalRoot . '/' . $dir . '/.gitkeep');
      }
    }

    // Prepare the settings file for installation.
    if (!$fs->exists($drupalRoot . '/sites/default/settings.php') and $fs->exists($drupalRoot . '/sites/default/default.settings.php')) {
      $fs->copy($drupalRoot . '/sites/default/default.settings.php', $drupalRoot . '/sites/default/settings.php');
      require_once $drupalRoot . '/core/includes/bootstrap.inc';
      require_once $drupalRoot . '/core/includes/install.inc';
      $settings['config_directories'] = [
        CONFIG_SYNC_DIRECTORY => (object) [
          'value' => Path::makeRelative($drupalFinder->getComposerRoot() . '/config', $drupalRoot),
          'required' => TRUE,
        ],
      ];
      drupal_rewrite_settings($settings, $drupalRoot . '/sites/default/settings.php');
      $fs->chmod($drupalRoot . '/sites/default/settings.php', 0666);
      $event->getIO()->write("Create a sites/default/settings.php file with chmod 0666");
    }
    // Create the files directory with chmod 0777.
    if (!$fs->exists($drupalRoot . '/sites/default/files')) {
      $oldmask = umask(0);
      $fs->mkdir($drupalRoot . '/sites/default/files', 0777);
      umask($oldmask);
      $event->getIO()->write("Create a sites/default/files directory with chmod 0777");
    }
    // Install git hooks.
    $hooksSrc = $drupalRoot . '/../scripts/git_hooks/hooks';
    $hooksDest = $drupalRoot . '/../.git/hooks';
    if ($fs->exists($hooksSrc)) {
      // Create .git/hooks if needed.
      if (!$fs->exists($hooksDest)) {
        $fs->mkdir($hooksDest);
      }
      // Copy all githooks files to .git/hooks.
      foreach (
        $iterator = new \RecursiveIteratorIterator(
          new \RecursiveDirectoryIterator($hooksSrc, \RecursiveDirectoryIterator::SKIP_DOTS),
          \RecursiveIteratorIterator::SELF_FIRST) as $item
      ) {
        if ($item->isDir()) {
          mkdir($hooksDest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
        }
        else {
          if (!$fs->exists($hooksDest . DIRECTORY_SEPARATOR . $iterator->getSubPathName())) {
            echo "\nInstalling git hook:\n";
            echo $hooksDest . DIRECTORY_SEPARATOR . $iterator->getSubPathName() . "\n\n";
            copy($item, $hooksDest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            $fs->chmod($hooksDest . DIRECTORY_SEPARATOR . $iterator->getSubPathName(), 0755);
          }
          else {
            echo "\nExists, not overwriting:\n";
            echo $hooksDest . DIRECTORY_SEPARATOR . $iterator->getSubPathName() . "\n\n";
          }
        }
      }
    }
    // Install multisite local settings.
    $dirs = array_filter(glob($drupalRoot . '/sites/*'), 'is_dir');
    foreach ($dirs as $dir) {
      $site = str_replace($drupalRoot . '/sites/', '', $dir);
      if (!$fs->exists($drupalRoot . "/sites/$site/settings.local.php") and $fs->exists($drupalRoot . "/../scripts/local/settings.local.php")) {
        $strings = file_get_contents($drupalRoot . '/../scripts/local/settings.local.php');
        $replaced = str_replace('[site]', $site, $strings);
        file_put_contents($drupalRoot . "/sites/$site/settings.local.php", $replaced);
        $fs->chmod($drupalRoot . "/sites/$site/settings.local.php", 0666);
        $event->getIO()->write("Create a sites/$site/settings.local.php file with chmod 0666");
      }
      else {
        $event->getIO()->write("Not updating $site/settings.local.php");
      }
      if (!$fs->exists($drupalRoot . "/sites/$site/services.local.yml") and $fs->exists($drupalRoot . '/../scripts/local/services.local.yml')) {
        $fs->copy($drupalRoot . '/../scripts/local/services.local.yml', $drupalRoot . "/sites/$site/services.local.yml");
      }
      else {
        $event->getIO()->write("Not updating $site/services.local.yml");
      }
    }
  }

  /**
   * Checks if the installed version of Composer is compatible.
   *
   * Composer 1.0.0 and higher consider a `composer install` without having a
   * lock file present as equal to `composer update`. We do not ship with a lock
   * file to avoid merge conflicts downstream, meaning that if a project is
   * installed with an older version of Composer the scaffolding of Drupal will
   * not be triggered. We check this here instead of in drupal-scaffold to be
   * able to give immediate feedback to the end user, rather than failing the
   * installation after going through the lengthy process of compiling and
   * downloading the Composer dependencies.
   *
   * @see https://github.com/composer/composer/pull/5035
   */
  public static function checkComposerVersion(Event $event) {
    $composer = $event->getComposer();
    $io = $event->getIO();

    $version = $composer::VERSION;

    // The dev-channel of composer uses the git revision as version number,
    // try to the branch alias instead.
    if (preg_match('/^[0-9a-f]{40}$/i', $version)) {
      $version = $composer::BRANCH_ALIAS_VERSION;
    }

    // If Composer is installed through git we have no easy way to determine if
    // it is new enough, just display a warning.
    if ($version === '@package_version@' || $version === '@package_branch_alias_version@') {
      $io->writeError('<warning>You are running a development version of Composer. If you experience problems, please update Composer to the latest stable version.</warning>');
    }
    elseif (Comparator::lessThan($version, '1.0.0')) {
      $io->writeError('<error>Drupal-project requires Composer version 1.0.0 or higher. Please update your Composer before continuing</error>.');
      exit(1);
    }
  }

}
