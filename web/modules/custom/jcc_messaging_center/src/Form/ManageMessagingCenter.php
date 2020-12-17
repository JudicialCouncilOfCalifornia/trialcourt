<?php

namespace Drupal\jcc_messaging_center\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;

/**
 * Dashboard to edit user groups.
 */
class ManageMessagingCenter extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'manage_messaging_center_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    self::setEntity(User::load($this->currentUser()->id()));

    /* @var $entity \Drupal\user\Entity\User */
    $form = parent::buildForm($form, $form_state);

    // dsm($form);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $form_state->setRedirect('entity.user.edit_form',
     ['user' => $this->entity->id()]);
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
    // $store = $this->tempstore->get('jcc_messaging_center');
    // $value = $store->get('member_email_' . $member_email);
    // return AccessResult::allowedIf(
    // $account->hasPermission('access content')
    // && ($access_key == $value || $account->getEmail() == $member_email));
    return AccessResult::allowedIf($account->hasPermission('access content'));
  }

}
