<?php

namespace Drupal\jcc_messaging_center\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use SendGrid\Client;
use SendGrid\Exception\SendgridException;
use SendGrid\Mail\Mail;
use SendGrid\Mail\MimeType;

/**
 * Provides SendGrid mail utilities for the messaging center.
 */
class JccMessagingCenterMailService {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs the mail service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('sendgrid_message');
  }

  /**
   * Sends a SendGrid message.
   */
  public function sendMail($email_title, $body, $email_to_sendgrid, $email_access_keys) {
    if (!empty(\Drupal::service('key.repository')->getKey('sendgrid'))) {
      $sendgrid_conf = \Drupal::config('sendgrid_integration.settings')
        ->get('test_defaults');
      $to = $sendgrid_conf['from_name'];
      $sendgrid_api_key = \Drupal::service('key.repository')
        ->getKey('sendgrid')
        ->getKeyValue();

      if (!is_array($email_to_sendgrid)) {
        $email_to_sendgrid = [$email_to_sendgrid];
      }
      if (!is_array($email_access_keys)) {
        $email_access_keys = [$email_access_keys];
      }

      $email_to_sendgrid_list = implode(', ', $email_to_sendgrid);

      // Creating email object.
      $sendgrid = new Client($sendgrid_api_key, ["turn_off_ssl_verification" => TRUE]);
      $email = new Mail();
      $email->setFrom($to, \Drupal::config('system.site')->get('name'));
      $email->setSubject($email_title);
      $email->addContent(MimeType::TEXT, $email_title);
      $email->addContent(MimeType::HTML, $body);
      $email->addGlobalHeader('X-Sent-Using', 'SendGrid-API');
      $email->addGlobalHeader('X-Transport', 'web');
      $email->addCategories(
        [
          'Email Alert',
          'Email Alert - ' . $email_title,
        ]
      );

      // Add recipients and substitutions for each recipient.
      foreach ($email_to_sendgrid as $index => $recipient) {
        $email->addTo($recipient, NULL, [
          '%member_email%' => $recipient,
          '%email_key%' => $email_access_keys[$index],
        ]);
      }

      try {
        \Drupal::logger('sendgrid_message')
          ->notice('firing send event to ' . $email_to_sendgrid_list);
        $sendGridResponse = $sendgrid->send($email);

        if ((int) $sendGridResponse->getCode() === 202) {
          \Drupal::messenger()->addMessage(t('Email successfully sent'));
        }
        else {
          // Show error.
          \Drupal::messenger()->addMessage(t('Email was not sent'));
        }
      }
      catch (SendgridException $e) {
        $eMessage = $e->getMessage();
        if (str_contains($eMessage, 'success')) {
          \Drupal::logger('sendgrid_message')->notice('SendGrid: sent');
        }
      }
    }
  }
}
