<?php

//namespace Drupal\jcc_directory\Plugin\Block;
namespace Drupal\jcc_elevated_embeds\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Directory Block'.
 *
 * @Block(
 *   id = "jrn_directory_block",
 *   admin_label = @Translation("JCC Directory Block")
 * )
 */
class JRNDirectoryBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
   $build = [];

    return [
    '#theme' => 'block__jrn_directory_block',
    '#title' => $this->t('JCC Directory'),
    '#content' => [
      '#markup' => '<p>This is a directory search block.</p>',
    ],
  ];


    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }
}
