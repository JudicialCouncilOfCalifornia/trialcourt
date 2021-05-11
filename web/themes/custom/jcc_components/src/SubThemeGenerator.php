<?php

declare(strict_types = 1);

namespace Drupal\jcc_components;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Sub theme generator.
 */
class SubThemeGenerator {

  /**
   * Filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  /**
   * Finder.
   *
   * @var \Symfony\Component\Finder\Finder
   */
  protected $finder;

  /**
   * Machine name old.
   *
   * @var string
   */
  protected $machineNameOld = '';

  /**
   * Dir.
   *
   * @var string
   */
  protected $dir = '';

  /**
   * Get dir.
   *
   * @return string
   *   This dir.
   */
  public function getDir(): string {
    return $this->dir;
  }

  /**
   * Set dir.
   *
   * @param string $dir
   *   Directory where a JCC Components starter kit already copied to.
   *
   * @return $this
   */
  public function setDir(string $dir) {
    $this->dir = $dir;

    return $this;
  }

  /**
   * Machine name.
   *
   * @var string
   */
  protected $machineName = '';

  /**
   * Get machine name.
   *
   * @return string
   *   This machine name.
   */
  public function getMachineName(): string {
    if (!$this->machineName) {
      return basename($this->getDir());
    }

    return $this->machineName;
  }

  /**
   * Set machine name.
   *
   * @param string $machineName
   *   Machine name string.
   *
   * @return object
   *   This object.
   */
  public function setMachineName(string $machineName) {
    $this->machineName = $machineName;

    return $this;
  }

  /**
   * Name.
   *
   * @var string
   */
  protected $name = '';

  /**
   * Get name.
   *
   * @return string
   *   This name.
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * Set name.
   *
   * @param string $name
   *   Name string.
   *
   * @return $this
   *   This object.
   */
  public function setName(string $name) {
    $this->name = $name;

    return $this;
  }

  /**
   * Description.
   *
   * @var string
   */
  protected $description = '';

  /**
   * Get description.
   *
   * @return string
   *   This description.
   */
  public function getDescription(): string {
    return $this->description;
  }

  /**
   * Set description.
   *
   * @param string $description
   *   Set the description.
   *
   * @return $this
   *   This object.
   */
  public function setDescription(string $description) {
    $this->description = $description;

    return $this;
  }

  /**
   * Constructor.
   */
  public function __construct() {
    $this->fs = new Filesystem();
    $this->finder = new Finder();
  }

  /**
   * Generate new theme object.
   *
   * @return $this
   *   This object.
   */
  public function generate() {
    return $this
      ->initMachineNameOld()
      ->modifyFileContents()
      ->renameFiles();
  }

  /**
   * Init old machine name.
   *
   * @return object
   *   This object.
   */
  protected function initMachineNameOld() {
    $dstDir = $this->getDir();
    $infoFiles = glob("$dstDir/*.info.yml");

    $this->machineNameOld = basename(reset($infoFiles), '.info.yml');

    return $this;
  }

  /**
   * Modify file contents.
   *
   * @return object
   *   This object.
   */
  protected function modifyFileContents() {
    $replacementPairs = $this->getFileContentReplacementPairs();
    foreach ($this->getFilesToMakeReplacements() as $fileName) {
      $this->modifyFileContent($fileName, $replacementPairs);
    }

    return $this;
  }

  /**
   * Rename files.
   *
   * @return object
   *   This object.
   */
  protected function renameFiles() {
    $machineNameNew = $this->getMachineName();
    if ($this->machineNameOld === $machineNameNew) {
      return $this;
    }

    foreach ($this->getFileNamesToRename() as $fileName) {
      $this->fs->rename($fileName, str_replace($this->machineNameOld, $machineNameNew, $fileName));
    }

    return $this;
  }

  /**
   * Modify file content.
   *
   * @return object
   *   This modified object.
   */
  protected function modifyFileContent(string $fileName, array $replacementPairs) {
    if (!$this->fs->exists($fileName)) {
      return $this;
    }

    $this->fs->dumpFile(
      $fileName,
      strtr($this->fileGetContents($fileName), $replacementPairs)
    );

    return $this;
  }

  /**
   * Get file names to rename.
   *
   * @return array
   *   List of matching files.
   */
  protected function getFileNamesToRename(): array {
    // Find all files within the theme that match *{KIT_NAME}*.
    return array_keys(iterator_to_array($this->finder->files()->name("*{$this->machineNameOld}*")->in($this->getDir())));
  }

  /**
   * Get file content replacement pairs.
   *
   * @return array
   *   Replacemnt array.
   */
  protected function getFileContentReplacementPairs(): array {
    return [
      'JCC_COMPONENTS_SUBTHEME_NAME' => $this->getName(),
      'JCC_COMPONENTS_SUBTHEME_DESCRIPTION' => $this->getDescription(),
      'JCC_COMPONENTS_SUBTHEME_MACHINE_NAME' => $this->getMachineName(),
      "\nhidden: true\n" => "\n",
    ];
  }

  /**
   * Get file list for string replacement.
   *
   * @return array
   *   File list for string replacement.
   */
  public function getFilesToMakeReplacements(): array {
    return array_keys(iterator_to_array($this->finder->files()->in($this->getDir())));
  }

  /**
   * Get the contents of the file.
   *
   * @param string $fileName
   *   Name of the file.
   *
   * @return string
   *   Content of the file.
   */
  protected function fileGetContents(string $fileName): string {
    $content = file_get_contents($fileName);
    if ($content === FALSE) {
      throw new RuntimeException("Could not read file '$fileName'", 1);
    }

    return $content;
  }

}
