<?php

namespace Drupal\jcc_subscriptions\Services;

use SendGrid\Exception;
use SendGrid\Email;

use MarkRoland\Emma\Client;
use SendGrid\Client as SClient;

use Drupal\views\Views;

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
    // TODO: Remove line below to stop emailing at every cron.
    $this->sendDigest();
    if ($this->shouldRun($now)) {
      // Checks if there is any news item to send.
      $view = Views::getView('news_digest');
      $view->get_total_rows = TRUE;
      $view->execute('default');
      $rows = $view->total_rows;

      if ($rows != 0) {
        $this->queueTasks();
        $this->state->set('jcc_subscriptions.last_cron', $now);
      }
      else {
        \Drupal::logger('jcc_subscriptions')->notice('No new newslink item have queued for email today.');
      }
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

    // TODO: Replace with final group id
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

    $email_body = \Drupal::service('renderer')->render(views_embed_view('news_digest', 'default'));

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
        ->setHtml('
          <table border="1" cellspacing="0" cellpadding="0" id="x_x_templateContainer" style="background-color:white;width:450pt;border:1pt solid #DDDDDD;">
            <tbody>
              <tr>
                <td valign="top" style="padding:0;border-style:none;">
                  <div align="center">
                    <table border="1" cellspacing="0" cellpadding="0" id="x_x_templateHeader" style="background-color:white;width:450pt;border-style:none none solid none;border-bottom-width:1pt;border-bottom-color:#DDDDDD;">
                      <tbody>
                        <tr>
                          <td style="padding:11.25pt 0;border-style:none;">
                            <div>
                              <p align="center" style="font-size:11pt;font-family:Calibri,sans-serif;text-align:center;margin:0;">
                                <b><span style="color:#202020;font-size:25.5pt;font-family:Arial,sans-serif;">
                                  <a href="%base_url%" target="_blank" rel="noopener noreferrer" data-auth="NotApplicable">
                                    <span style="color:#0088CC;font-weight:normal;text-decoration:none;">
                                      <img data-imagetype="External" src="%base_url%/sites/default/files/newsroom/NewsroomDrupalBanner%20Blue.png" originalsrc="%base_url%/sites/default/files/newsroom/NewsroomDrupalBanner%20Blue.png" border="0" id="x_x__x0000_i1025" style="width:450pt;height:32.99pt;">
                                    </span>
                                  </a></span>
                                </b>
                              </p>
                              </div>
                            <div style="margin-right:24pt;margin-left:24pt;">
                              <p style="font-size:11pt;font-family:Calibri,sans-serif;margin:0;">
                                <span style="color:#202020;font-size:10pt;font-family:Arial,sans-serif;">
                                  <a href="%base_url%" target="_blank" rel="noopener noreferrer" data-auth="NotApplicable">
                                    <span style="color:#0088CC;text-decoration:none;">See NewsLinks in California Courts Newsroom</span>
                                  </a>
                                </span>
                              </p>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </td>
              </tr>
              <tr>
                <td valign="top" style="padding:0;border-style:none;">
                  <div align="center">
                    <table border="0" cellspacing="0" cellpadding="0" id="x_x_templateBody" style="width:450pt;">
                      <tbody>
                        <tr>
                          <td valign="top" style="background-color:white;padding:0;">
                            <table border="0" cellspacing="0" cellpadding="0" style="width:100%;">
                              <tbody>
                                <tr>
                                  <td valign="top" style="padding:10pt;">
                                    <h1 style="color:#202020;font-size:27pt;font-family:Arial,sans-serif;font-weight:bold;margin:0 0 15pt 0;">
                                      <span style="font-size:16.5pt;">California Courts NewsLinks Digest</span>
                                    </h1>
                                    <h3>%today_date%</h3>
                                    <br>
                                    <div>%email_body%</div>
                                    <br>
                                    <p><a href="%base_url%/subscriptions/%member_email%/manage">manage your preferences</a><br>
                                    or <a href="%base_url%/subscriptions/%member_id%/delete-all">opt out</a> from all communications.</p>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>')
        ->addSubstitution('%email_body%', [$email_body])
        ->addSubstitution('%today_date%', [date("F j, Y")])
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
