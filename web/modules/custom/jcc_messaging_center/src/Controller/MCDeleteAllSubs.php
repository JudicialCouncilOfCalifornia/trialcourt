<?php

namespace Drupal\jcc_messaging_center\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;

use SendGrid\Client as SClient;
use SendGrid\Exception;
use SendGrid\Email;

/**
 * Deletes all groups from user.
 */
class MCDeleteAllSubs extends ControllerBase {

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
  public function content(string $member_email = '', string $access_key = '') {
    $store = $this->tempstore->get('jcc_messaging_center');
    $token_value = $store->get('member_email_' . $member_email);
    $build = [];

    if (!($token_value == $access_key)){
      $build = [
        '#markup' => '
        <div class="jcc-text-section-aside__container jcc-text-section-aside-secondary__container">
            <div class="body">
                <br><br><p>This link is expired, we sent you a new email</p><br><br>
            </div>
        </div>',
        ];
      jcc_messaging_center_send_email_from_error_unsubscribe($member_email);
    } else {
      $build = [
        '#markup' => '
        <div class="jcc-text-section-aside__container jcc-text-section-aside-secondary__container">
            <div class="body">
                <br><br><p><a class="usa-button usa-button--primary" href="/messaging-center/' . $member_email . '/delete-all/confirmed/' . $access_key . '">' . $this->t("Unsubscribe from all communications") . '</a></p><br><br>
            </div>
        </div>',
      ];
    }

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
    return AccessResult::allowedIf($access_key != '' && $member_email != '');
  }
}

/**
 * Send email through sendgrid after an unvalid token.
 *
 * @param string $member_email
 *   Member email.
 */
function jcc_messaging_center_send_email_from_error_unsubscribe(string $to_email = '') {
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
