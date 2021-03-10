<?php

namespace Drupal\jcc_subscriptions\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use SendGrid\Client as SClient;
use SendGrid\Exception;

/**
 * Processes tasks for subscriptions module.
 *
 * @QueueWorker(
 *   id = "send_subscriptions_queue",
 *   title = @Translation("Subscriptions: Send Digest."),
 *   cron = {"time" = 90}
 * )
 */
class SendSubscriptionsQueueWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    \Drupal::logger('jcc_subscriptions')->notice('Subscriptions --- Sendgrid queue worker loaded.');
    $sendgrid_api_key = \Drupal::service('key.repository')->getKey('newsroom_sendgrid')->getKeyValue();
    // DOC: https://github.com/Fastglass-LLC/sendgrid-php-example/blob/master/sendgrid-php-example-send.php
    // Creating email object.
    $sendgrid = new SClient($sendgrid_api_key, ["turn_off_ssl_verification" => TRUE]);

    try {
      $sendGridResponse = $sendgrid->send($data['email']);

      if ($sendGridResponse->getCode() == 200 || $sendGridResponse->getCode() == "200") {
        $log_message_sendgrid = 'Subscriptions --- Sendgrid response: Email(s) successfully sent to ' . $data['email_ammount'] . ' recipient(s)';
        \Drupal::logger('jcc_subscriptions')->notice($log_message_sendgrid);
      }
      else {
        // Show error.
        \Drupal::logger('jcc_subscriptions')->notice('Subscriptions --- Sendgrid response: Email(s) was not sent.)');
      }
    }
    catch (Exception $e) {
      $eMessage = $e->getMessage();
      if (strpos($eMessage, 'success') !== FALSE) {
      }
    }
  }

}
