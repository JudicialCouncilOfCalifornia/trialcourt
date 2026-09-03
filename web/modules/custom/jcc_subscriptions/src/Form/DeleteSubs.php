<?php

namespace Drupal\jcc_subscriptions\Form;

use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MarkRoland\Emma\Client;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SendGrid\Client as SClient;
use SendGrid\Email;

/**
 * Deletes all groups from user.
 */
class DeleteSubs extends FormBase {

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
    return 'delete_subs_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $member_id = '', string $access_key = '') {

    $emma_config = self::config('webform_myemma.settings');
    $emma = new Client($emma_config->get('account_id'), $emma_config->get('public_key'), $emma_config->get('private_key'));
    $member_email = $member_id;

    $store = $this->tempstore->get('jcc_subscriptions');
    $token_value = $store->get('member_email_' . $member_email);

    if (!($token_value == $access_key)) {
      $form['invalid_message'] = [
        '#prefix' => '<h2>',
        '#suffix' => '</h2>',
        '#markup' => t('This link is expired or invalid.'),
        '#weight' => -100,
        '#value' => true,
      ];

      $form['invalid'] = array(
        '#type' => 'hidden',
        '#value' => true,
      );

      $form['user_email'] = [
        '#type' => 'hidden',
        '#value' => $member_email,
      ];

      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Get a new link'),
        '#button_type' => 'primary',
      ];
    }
    else {
      $form['user_email'] = [
        '#type' => 'value',
        '#value' => $member_email,
      ];

      $form['invalid_message'] = [
        '#prefix' => '<h2>',
        '#suffix' => '</h2>',
        '#markup' => t('Manage preferences'),
        '#weight' => -100,
        '#value' => true,
      ];

      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Unsubscribe from all communications'),
        '#button_type' => 'primary',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasValue('invalid')){
      jcc_subscriptions_send_email_from_error($form_state->cleanValues()->getValues()['user_email']);
      $this->messenger()->addStatus($this->t('An email has been sent to ' . $form_state->cleanValues()->getValues()['user_email']));
    } else {
      $emma_config = \Drupal::config('webform_myemma.settings');
      $emma = new Client($emma_config->get('account_id'), $emma_config->get('public_key'), $emma_config->get('private_key'));
      $user_req = $emma->get_member_detail_by_email($form_state->cleanValues()->getValues()['user_email']);
      $user_emma_id = $user_req->member_id;

      $emma->remove_member_from_all_groups($user_emma_id);
      $this->messenger()->addStatus($this->t('<div class="jcc-text-section-aside__container jcc-text-section-aside-secondary__container"><div class="body"><br><br><p>' . $form_state->cleanValues()->getValues()['user_email'] . ' ' . $this->t('has been removed from all communications.') . '</p><br><br></div></div>'));
    }
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
  public function access(AccountInterface $account, string $member_id = '', string $access_key = '') {
    return AccessResult::allowedIf($access_key != '' && $member_id != '');
  }

}

/**
 * Send email through sendgrid after an unvalid token.
 *
 * @param string $to_email
 *   Member email.
 */
function jcc_subscriptions_send_email_from_error(string $to_email = '') {
  global $base_url;

  $tempstore = \Drupal::service('tempstore.shared');
  $store = $tempstore->get('jcc_subscriptions');

  // Gathering emails to send emails to.
  $email_to_sendgrid = [$to_email];
  $id_to_sendgrid = [];
  $email_access_keys = [];
  $id_access_keys = [];

  $email_key = \Drupal::service('password_generator')->generate();
  array_push($email_access_keys, $email_key);
  $store->set('member_email_' . $to_email, $email_key);

  // Getting from email.
  if (!empty(\Drupal::service('key.repository')->getKey('newsroom_sendgrid'))) {
    $sendgrid_conf = \Drupal::config('sendgrid_integration.settings')->get('test_defaults');
    $to = $sendgrid_conf['from_name'];
    $sendgrid_api_key = \Drupal::service('key.repository')->getKey('newsroom_sendgrid')->getKeyValue();

    $body = str_replace(
      [
        '%base_url%',
        '%email_key%',
      ],
      [
        $base_url,
        $email_key,
      ],
      '
          <h2>Preferences management</h2>
          <p>Please use the following link to update your preferences</p>
          <br/>
          <p><a href="%base_url%/subscriptions/%member_email%/manage/%email_key%">Manage your preferences</a><br>
        '
    );

    /** @var \Drupal\jcc_messaging_center\Service\JccMessagingCenterMailService $mail_service */
    $mail_service = \Drupal::service('jcc_messaging_center.mail_service');
    $mail_service->sendMail('Preferences management', $body, $email_to_sendgrid, $email_access_keys);
  }
}
