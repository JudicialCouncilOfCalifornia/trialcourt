<?php

namespace Drupal\jcc_messaging_center\Form;

use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SendGrid\Client as SClient;
use SendGrid\Exception;
use SendGrid\Email;

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
  public function buildForm(array $form, FormStateInterface $form_state, string $member_email = '', string $access_key = '') {
    $store = $this->tempstore->get('jcc_messaging_center');
    $token_value = $store->get('member_email_' . $member_email);

    if (!($token_value == $access_key)){
      $form['temp1'] = [
        '#prefix' => '<h2>',
        '#suffix' => '</h2>',
        '#markup' => t('This link is expired, we sent you a new email'),
        '#weight' => -100,
      ];
      jcc_messaging_center_send_email_from_error_management($member_email);
    } else {
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
        '#markup' => t('Manage preferences'),
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
    }
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
//    $store = $this->tempstore->get('jcc_messaging_center');
//    $value = $store->get('member_email_' . $member_email);

//    return AccessResult::allowedIf(
//      $account->hasPermission('access content')
//      && ($access_key == $value || $account->getEmail() == $member_email));
    return AccessResult::allowedIf($access_key != '' && $member_email != '');
  }

}

/**
 * Send email through sendgrid after an unvalid token.
 *
 * @param string $member_email
 *   Member email.
 */
function jcc_messaging_center_send_email_from_error_management(string $to_email = '') {
  global $base_url;

  //Creating new token in tempshare
  $email_key = user_password();
  $tempstore = \Drupal::service('tempstore.shared');
  $store = $tempstore->get('jcc_messaging_center');
  $store->set('member_email_' . $to_email, $email_key);

  if (!empty(\Drupal::service('key.repository')->getKey('sendgrid'))) {
    $sendgrid_conf = \Drupal::config('sendgrid_integration.settings')->get('test_defaults');
    $to = $sendgrid_conf['from_name'];
    $sendgrid_api_key = \Drupal::service('key.repository')->getKey('sendgrid')->getKeyValue();

    $body = str_replace(
      [
        '%base_url%',
        '%$email_key%'
      ],
      [
        $base_url,
        $email_key
      ],
      '
          <h2>Preferences management</h2>
          <p>Please use the following link to update your preferences</p>
          <br/>
          <br/>
          <p><a href="%base_url%/messaging-center/%member_email%/manage/%email_key%">manage your preferences</a><br>
          or <a href="%base_url%/messaging-center/%member_email%/delete-all/%email_key%">opt out</a> from all communications.</p>
        '
    );

    // DOC: https://github.com/Fastglass-LLC/sendgrid-php-example/blob/master/sendgrid-php-example-send.php
    // Creating email object.
    $sendgrid = new SClient($sendgrid_api_key, ["turn_off_ssl_verification" => TRUE]);
    $email = new Email();
    $email->setSmtpapiTos([$to_email])
      ->setFrom($to)
      ->setFromName(\Drupal::config('system.site')->get('name'))
      ->setSubject('Preferences management')
      ->setText('Preferences management')
      ->setHtml($body)
      ->addSubstitution('%member_email%', [$to_email])
      ->addSubstitution('%email_key%', [$email_key])
      ->addHeader('X-Sent-Using', 'SendGrid-API')
      ->addHeader('X-Transport', 'web')
      ->setCategories(
        [
          'Email Alert',
          'Email Alert - Preferences management',
        ]
      );

    try {
      \Drupal::logger('sendgrid_message')->notice('firing send event to ' . $to_email);
      $sendGridResponse = $sendgrid->send($email);

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
