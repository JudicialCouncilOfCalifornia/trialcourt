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
    $courts = \Drupal::entityTypeManager()->getStorage('jcc_court')->loadByProperties(['status' => 1]);
    $court_options = [];
    foreach ($courts as $court) {
      $name2 = $court->get('name_2')->value ?? '';
      $name3 = $court->get('name_3')->value ?? '';
      $combined_name = trim($name2 . ' ' . $name3);
      $court_options[$court->id()] = $combined_name;
    }
    return [
      '#theme' => 'block__jrn_directory',
      '#title' => $this->t('JCC Directory'),
      'jcc_court_options' => $court_options,
    ];
  }

}
