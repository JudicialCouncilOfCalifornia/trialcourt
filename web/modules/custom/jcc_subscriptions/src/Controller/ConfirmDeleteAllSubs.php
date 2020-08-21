<?php

namespace Drupal\jcc_subscriptions\Controller;

use Drupal\Core\Controller\ControllerBase;
use MarkRoland\Emma\Client;

/**
 * Deletes all groups from user.
 */
class ConfirmDeleteAllSubs extends ControllerBase {

  /**
   * Returns a render-able array.
   */
  public function content(string $member_id = '') {
    $emma_config = \Drupal::config('webform_myemma.settings');
    $emma = new Client($emma_config->get('account_id'), $emma_config->get('public_key'), $emma_config->get('private_key'));
    $emma->remove_member_from_all_groups($member_id);
    $user = $emma->get_member_detail($member_id);
    $build = [
      '#markup' => '<div class="jcc-text-section-aside__container jcc-text-section-aside-secondary__container"><div class="body"><p>' . $user->email . ' ' . $this->t('has been removed from all communications.') . '</p></div></div>',
    ];

    return $build;
  }

}
