<?php

namespace Drupal\jcc_subscriptions\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;

/**
 * Deletes all groups from user.
 */
class DeleteAllSubs extends ControllerBase {

  /**
   * Temp store.
   *
   * @var Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * Class constructor.
   */
  public function __construct(SharedTempStoreFactory $tempstore) {
    $this->tempstore = $tempstore;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('tempstore.shared')
    );
  }

  /**
   * Returns a render-able array.
   */
  public function content(string $member_id = '', string $access_key = '') {
    $build = [
      '#markup' => '
        <div class="jcc-text-section-aside__container jcc-text-section-aside-secondary__container">
            <div class="body">
                <br><br><p><a class="usa-button usa-button--primary" href="/subscriptions/' . $member_id . '/delete-all/confirmed/' . $access_key . '">' . $this->t("Unsubscribe from all communications") . '</a></p><br><br>
            </div>
        </div>',
    ];

    return $build;
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param string $member_id
   *   Member email.
   * @param string $access_key
   *   Member access key.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, string $member_id = '', string $access_key = '') {
    $store = $this->tempstore->get('jcc_subscriptions');
    $value = $store->get('member_id_' . $member_id);

    return AccessResult::allowedIf(
      $account->hasPermission('access content')
      && $access_key == $value);
  }

}
