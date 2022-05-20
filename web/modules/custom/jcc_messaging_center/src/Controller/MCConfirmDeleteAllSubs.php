<?php

namespace Drupal\jcc_messaging_center\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deletes all groups from user.
 */
class MCConfirmDeleteAllSubs extends ControllerBase {

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
  public function delete(string $member_email = '', string $access_key = '') {
    $user = user_load_by_mail($member_email);
    $user->set('field_group', []);
    $user->save();

    $build = [
      '#markup' => '<div class="jcc-text-section-aside__container jcc-text-section-aside-secondary__container"><div class="body"><br><br><p>' . $member_email . ' ' . $this->t('has been removed from all communications.') . '</p><br><br></div></div>',
    ];

    return $build;
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param string $member_email
   *   Member email.
   * @param string $access_key
   *   Member access key.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, string $member_email = '', string $access_key = '') {
    $store = $this->tempstore->get('jcc_messaging_center');
    $value = $store->get('member_email_' . $member_email);

    return AccessResult::allowedIf(
      $account->hasPermission('access content')
      && $access_key == $value);
  }

}
