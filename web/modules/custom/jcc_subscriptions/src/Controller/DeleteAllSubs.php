<?php

namespace Drupal\jcc_subscriptions\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Deletes all groups from user.
 */
class DeleteAllSubs extends ControllerBase {

  /**
   * Returns a render-able array.
   */
  public function content(string $member_id = '') {
    $build = [
      '#markup' => '
        <div class="jcc-text-section-aside__container jcc-text-section-aside-secondary__container">
            <div class="body">
                <br><br><p><a class="usa-button usa-button--primary" href="/subscriptions/' . $member_id . '/delete-all/confirmed">' . $this->t("Unsubscribe from all communications.") . '</a></p><br><br>
            </div>
        </div>',
    ];

    return $build;
  }

}
