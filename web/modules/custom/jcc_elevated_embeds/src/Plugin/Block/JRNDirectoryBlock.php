<?php

namespace Drupal\jcc_elevated_embeds\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Directory Block'.
 *
 * @Block(
 *   id = "jrn_directory",
 *   admin_label = @Translation("JRN Directory block")
 * )
 */
class JRNDirectoryBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'block__jrn_directory_block',
      '#title' => $this->t('JCC Directory'),
      '#content' => [
        '#markup' => '<p>This is a directory search block.</p>',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
