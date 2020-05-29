<?php

namespace Drupal\jcc_glossify_path_exclude\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 *
 * @Block(
 *   id = "glossifypathexclusion",
 *   admin_label = @Translation("JCC Glossify Path exclusion"),
 *   category = "JCC custom path exclusion module"
 * )
 */
class JccGlossifyPathsExclude extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [];
  }

}
