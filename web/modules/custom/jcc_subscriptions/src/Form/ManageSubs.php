<?php

namespace Drupal\jcc_subscriptions\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use MarkRoland\Emma\Client;
use SendGrid\Client as SClient;
use SendGrid\Email;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deletes all groups from user.
 */
class ManageSubs extends FormBase {

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
  public function buildForm(array $form, FormStateInterface $form_state, string $member_email = '', string $access_key = '') {

    $emma_config = self::config('webform_myemma.settings');
    $emma = new Client($emma_config->get('account_id'), $emma_config->get('public_key'), $emma_config->get('private_key'));
    $user = $emma->get_member_detail_by_email($member_email);

    $store = $this->tempstore->get('jcc_subscriptions');
    $token_value = $store->get('member_email_' . $member_email);

    if (!($token_value == $access_key) && !(\Drupal::currentUser()->isAuthenticated())) {
      $form['invalid_message'] = [
        '#prefix' => '<h2>',
        '#suffix' => '</h2>',
        '#markup' => t('This link is expired or invalid.'),
        '#weight' => -100,
        '#value' => TRUE,
      ];

      $form['invalid'] = array(
        '#type' => 'hidden',
        '#value' => TRUE,
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
      // Getting groups from myemma / only keeping ones with naming convention.
      $myemma_groups = $emma->list_groups();
      $form_groups = [];
      foreach ($myemma_groups as $group) {
        if (strpos($group->group_name, 'Newsroom mailing') !== FALSE
          && stripos($group->group_name, 'internal-only') == FALSE) {
          $form_groups[$group->member_group_id] =
            str_replace('Newsroom mailing ', '', $group->group_name);
        }
      }

      // Creating list of groups form myEmma.
      $form['myemma_groups'] = [
        '#type' => 'checkboxes',
        '#options' => $form_groups,
        '#title' => $this->t('Manage subscriptions:'),
      ];

      // Populating default values.
      if (!isset($user->error)) {
        // pre-populate active categories.
        $emma_user_id = $user->member_id;
        $user_groups_object = $emma->list_member_groups($emma_user_id);
        $user_groups = [];
        foreach ($user_groups_object as $group_objects) {
          array_push($user_groups, $group_objects->member_group_id);
        }
        $form['myemma_groups']['#default_value'] = $user_groups;
      }

      $form['email'] = [
        '#type' => 'value',
        '#value' => $member_email,
      ];

      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#button_type' => 'primary',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasValue('invalid')) {
      jcc_subscriptions_send_email_from_error_management($form_state->cleanValues()->getValues()['user_email']);
      $this->messenger()->addStatus($this->t('An email has been sent to ' . $form_state->cleanValues()->getValues()['user_email']));
    }
    else {
      $member_email = $form['email']['#value'];
      $emma_config = self::config('webform_myemma.settings');
      $emma = new Client($emma_config->get('account_id'), $emma_config->get('public_key'), $emma_config->get('private_key'));

      $user_req = $emma->get_member_detail_by_email($member_email);
      $user_emma_id = $user_req->member_id;
      // Create / Update user.
      $fields = [
        'first_name' => $member_email,
      ];
      $groups = [];
      $groups_to_remove = [];
      foreach ($form_state->cleanValues()->getValues()['myemma_groups'] as $key => $val) {
        if ($val !== 0) {
          $groups[] = $val;
        }
        else {
          $groups_to_remove[] = $key;
        }
      }
      $emma->import_single_member($member_email, $fields, $groups);
      // Need an extra call to account for groups to remove.
      $emma->remove_member_from_groups($user_emma_id, $groups_to_remove);
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
  public function access(AccountInterface $account, string $member_email = '', string $access_key = '') {
    return AccessResult::allowedIf($access_key != '' && $member_email != '');
  }

}

/**
 * Send email through sendgrid after an unvalid token.
 *
 * @param string $to_email
 *   Member email.
 */
function jcc_subscriptions_send_email_from_error_management(string $to_email = '') {
  global $base_url;

  $tempstore = \Drupal::service('tempstore.shared');
  $store = $tempstore->get('jcc_subscriptions');

  // Gathering emails to send emails to.
  $email_to_sendgrid = [$to_email];
  $id_to_sendgrid = [];
  $email_access_keys = [];
  $id_access_keys = [];

  $email_key = user_password();
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
        '%$email_key%',
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

    // DOC: https://github.com/Fastglass-LLC/sendgrid-php-example/blob/master/sendgrid-php-example-send.php
    // Creating email object.
    $sendgrid = new SClient($sendgrid_api_key, ["turn_off_ssl_verification" => TRUE]);
    $email = new Email();
    $email->setSmtpapiTos($email_to_sendgrid)
      ->setFrom($to)
      ->setFromName(\Drupal::config('system.site')->get('name'))
      ->setSubject('Preferences management')
      ->setText('Preferences management')
      ->setHtml($body)
      ->addSubstitution('%member_id%', $id_to_sendgrid)
      ->addSubstitution('%member_email%', $email_to_sendgrid)
      ->addSubstitution('%id_key%', $id_access_keys)
      ->addSubstitution('%email_key%', $email_access_keys)
      ->addHeader('X-Sent-Using', 'SendGrid-API')
      ->addHeader('X-Transport', 'web')
      ->setCategories(
        [
          'Alert',
          'Alert - Preferences Management',
        ]
      );

    // Issue when simply calling $sendgrid->send($email);
    // fix from https://www.drupal.org/project/sendgrid_integration/issues/3041660#comment-13784755
    // Send an email using the template stored in SendGrid.
    try {
      $sendGridResponse = $sendgrid->send($email);
      \Drupal::logger('sendgrid_message')->notice('(Manage subs) firing send event to ' . $to_email);
      if ($sendGridResponse->getCode() == 200 || $sendGridResponse->getCode() == "200") {
        \Drupal::messenger()->addMessage(t('Email successfully sent'));
      }
      else {
        // Show error.
        \Drupal::messenger()->addMessage(t('Email was not sent'));
      }
    }
    catch (Exception $e) {
      $eMessage = $e->getMessage();
      if (strpos($eMessage, 'success') !== FALSE) {
        \Drupal::logger('sendgrid_message')->notice('SendGrid: sent');
      }
    }
  }
}
