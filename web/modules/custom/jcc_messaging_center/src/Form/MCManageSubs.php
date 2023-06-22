<?php

namespace Drupal\jcc_messaging_center\Form;

use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deletes all groups from user.
 */
class MCManageSubs extends FormBase {

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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'manage_subs_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $member_email = '') {

    $vid = 'user_groups';
    $user_groups_raw = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);

    $user_groups = [];
    foreach ($user_groups_raw as $group) {
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($group->tid);
      $user_groups[$group->tid] = $term->label();
    }

    $form['title_header'] = [
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
      '#markup' => t('Notifications from the Supreme Court of California'),
      '#weight' => -100,
    ];
    $form['text_header'] = [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => t('<br />The following sections of the Court’s website are updated frequently. <br />Select the items you’d like ' . $member_email . ' to be notified when content is posted.<br /><br />'),
      '#weight' => -100,
    ];

    // Creating list of user groups.
    $form['user_groups'] = [
      '#type' => 'checkboxes',
      '#options' => $user_groups,
    ];

    // Populating default values.
    $user_object = user_load_by_mail($member_email);
    $active_groups = [];
    if($user_object->get('field_jcc_messaging_center_group')->getValue()){
      foreach ($user_object->get('field_jcc_messaging_center_group')->getValue() as $active_group) {
        array_push($active_groups, $active_group['target_id']);
      }
      $form['user_groups']['#default_value'] = $active_groups;
    };

    $form['user_email'] = [
      '#type' => 'hidden',
      '#value' => $member_email,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user_email = $form_state->cleanValues()->getValues()['user_email'];
    $user_groups = $form_state->cleanValues()->getValues()['user_groups'];
    $user = user_load_by_mail($user_email);

    $user->set('field_jcc_messaging_center_group', $user_groups);
    $user->save();
    
    $this->messenger()->addStatus($this->t('Your preferences have been updated.'));

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
      && ($access_key == $value || $account->getEmail() == $member_email));
  }

}
