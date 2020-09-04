<?php

namespace Drupal\jcc_subscriptions\Services;

use SendGrid\Exception;
use SendGrid\Email;

use MarkRoland\Emma\Client;
use SendGrid\Client as SClient;

/**
 * Class JCCSubscriptionsDigestCron.
 */
class JCCSubscriptionsDigestCron {

  /**
   * Action on cron run.
   */
  public function cron() {
    $this->state = \Drupal::state();
    $now = \Drupal::time()->getRequestTime();
    $this->send_digest();
    if ($this->shouldRun($now)) {
      $this->queueTasks();
      $this->state->set('jcc_subscriptions.last_cron', $now);
    }
  }

  /**
   * Test if cron should run.
   */
  public function shouldRun($now) {
    $scheduled = '17:00';
    $timezone = new \DateTimeZone('America/Los_Angeles');

    $timestamp_last = $this->state->get('jcc_subscriptions.last_cron') ?? 0;
    $last = \DateTime::createFromFormat('U', $timestamp_last)
      ->setTimezone($timezone);
    $next = clone $last;

    $next->setTime(...explode(':', $scheduled));
    if ($next->getTimestamp() <= $last->getTimestamp()) {
      $next->modify('+1 day');
    }
    return $next->getTimestamp() <= $now;
  }

  /**
   * Add task to the queue.
   */
  public function queueTasks() {
    \Drupal::logger('jcc_subscriptions')->notice('TEST : SEND EMAIL');
    $this->sendDigest();
  }

  /**
   * Trigger sendgrid emailing.
   */
  public function sendDigest() {
    global $base_url;
    // Getting data from myemma.
    $emma_config = \Drupal::config('webform_myemma.settings');
    $emma = new Client($emma_config->get('account_id'), $emma_config->get('public_key'), $emma_config->get('private_key'));

    // TEST 2 : 13606916
    // test digest : 13977604.
    $emma_group = '13977604';

    // Gathering emails to send emails to.
    $email_to_sendgrid = [];
    $id_to_sendgrid = [];
    $users_in_group = $emma->list_group_members($emma_group);
    foreach ($users_in_group as $user_group) {
      if (!in_array($user_group->email, $email_to_sendgrid, TRUE)) {
        array_push($email_to_sendgrid, $user_group->email);
        // Building array of ID's for opting out urls.
        array_push($id_to_sendgrid, $user_group->member_id);
      }
    }

    \Drupal::logger('jcc_subscriptions')->notice('$email_to_sendgrid');

    // $email_body = views_embed_view('news_digest', 'default');
    $email_body = \Drupal::service('renderer')->render(views_embed_view('news_digest', 'default'));

    \Drupal::logger('jcc_subscriptions')->notice($email_body);

    // Getting from email.
    if (!empty(\Drupal::service('key.repository')->getKey('newsroom_sendgrid'))) {
      $sendgrid_conf = \Drupal::config('sendgrid_integration.settings')->get('test_defaults');
      $to = $sendgrid_conf['from_name'];
      $sendgrid_api_key = \Drupal::service('key.repository')->getKey('newsroom_sendgrid')->getKeyValue();
      // DOC: https://github.com/Fastglass-LLC/sendgrid-php-example/blob/master/sendgrid-php-example-send.php
      // Creating email object.
      $sendgrid = new SClient($sendgrid_api_key, ["turn_off_ssl_verification" => TRUE]);
      $email = new Email();
      $email->addTo($email_to_sendgrid)
        ->setFrom($to)
        ->setSubject('News digest form newsroom')
        ->setText('News digest form newsroom')
        ->setHtml('%email_body%')
        // ->setHtml('<div>TEST</div>')
        ->addSubstitution('%email_body%', [$email_body])
        ->addSubstitution('%member_id%', $id_to_sendgrid)
        ->addSubstitution('%member_email%', $email_to_sendgrid)
        ->addSubstitution('%emma_account%', [$emma_config->get('account_id')])
        ->addSubstitution('%base_url%', [$base_url])
        ->addHeader('X-Sent-Using', 'SendGrid-API')
        ->addHeader('X-Transport', 'web');

      // Issue when simply calling $sendgrid->send($email);
      // fix from https://www.drupal.org/project/sendgrid_integration/issues/3041660#comment-13784755
      // Send an email using the template stored in SendGrid.
      try {
        $sendGridResponse = $sendgrid->send($email);

        if ($sendGridResponse->getCode() == 200 || $sendGridResponse->getCode() == "200") {
          drupal_set_message(t('Email successfully sent'));
        }
        else {
          // Show error.
          drupal_set_message(t('Email was not sent'));
        }
      }
      catch (Exception $e) {
        $eMessage = $e->getMessage();
        if (strpos($eMessage, 'success') !== FALSE) {
        }
      }
    }
  }

}
