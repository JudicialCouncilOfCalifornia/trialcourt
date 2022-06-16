<?php

namespace Drupal\jcc_subscriptions\Services;

use SendGrid\Email;

use JudicialCouncil\Emma\JccClient;

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

    $jcc_config = \Drupal::config('jcc_subscriptions.settings');

    if ($jcc_config->get('newslink_digest_debug')) {
      \Drupal::logger('jcc_subscriptions')->notice('Subscriptions --- Newslink digest debug enabled.');
      // $this->sendDigest();
      $this->queueTasks();
    }

    if ($this->shouldRun($now, $jcc_config->get('newslink_digest_time'))) {
      // Checks if there is any news item to send.
      $view = Views::getView('news_digest');
      $view->get_total_rows = TRUE;
      $view->execute('default');
      $rows = $view->total_rows;

      if ($rows != 0) {
        $this->queueTasks();
        $this->state->set('jcc_subscriptions.last_cron', $now);
        $log_message = 'Subscriptions --- Newslink digest ran with ' . $rows . ' results, at ' . $now;
        \Drupal::logger('jcc_subscriptions')->notice($log_message);
      }
      else {
        $this->state->set('jcc_subscriptions.last_cron', $now);
        \Drupal::logger('jcc_subscriptions')->notice('No new newslink item are queued for email today.');
      }
    }

    //Test if cron should run a follow up (in case initial digest failed)
    $end_of_checks_temp = strtotime($jcc_config->get('newslink_digest_time')) + 60*60; // Adding 1hour to check for that failed queueworder
    $end_of_checks = date('H:i', $end_of_checks_temp);

    if ($this->shouldRun($now, $jcc_config->get($end_of_checks))) {
      $subscriptionQueue = \Drupal::queue('send_subscriptions_queue');
      if ($subscriptionQueue->numberOfItems() != 0) {
        $subscriptionQueue->claimItem(9000); // will try to execute this task for 15mins
        \Drupal::logger('jcc_subscriptions')->notice('Subscriptions --- Tried to execute existing Digest queue worker');
      }
    }
  }

  /**
   * Test if cron should run.
   */
  public function shouldRun($now, $scheduled = '17:00') {
     if (!isset($_ENV['PANTHEON_ENVIRONMENT']) || $_ENV['PANTHEON_ENVIRONMENT'] != 'live') {
          return FALSE;
     }
    $timezone = new \DateTimeZone('America/Los_Angeles');

    $timestamp_last = $this->state->get('jcc_subscriptions.last_cron') ?? 0;
    $last = \DateTime::createFromFormat('U', $timestamp_last)
      ->setTimezone($timezone);
    $next = clone $last;

    $next->setTime(...explode(':', $scheduled));

    if (($next->getTimestamp() <= $last->getTimestamp())) {
      $next->modify('+1 day');
    }

    $cron_timing_log_message = 'Subscriptions --- Last JCC_subs cron: ' . date('m/d/Y H:i:s', $last->getTimestamp()) . ' --- Next:  ' . date('m/d/Y H:i:s', $next->getTimestamp());
    \Drupal::logger('jcc_subscriptions')->notice($cron_timing_log_message);

    return ($next->getTimestamp() <= $now) && (time() >= strtotime($scheduled));
  }

  /**
   * Add task to the queue.
   */
  public function queueTasks() {
    $log_message_sendgrid = 'Subscriptions --- QUEUETASKS() START';
    \Drupal::logger('jcc_subscriptions')->notice($log_message_sendgrid);
    $this->sendDigest();
    $this->flagNewsItems();
    $log_message_sendgrid = 'Subscriptions --- QUEUETASKS() END';
    \Drupal::logger('jcc_subscriptions')->notice($log_message_sendgrid);
  }

  /**
   * Flag news items which have been sent.
   */
  public function flagNewsItems() {
    $view = Views::getView('news_digest');
    $view->get_total_rows = TRUE;
    $view->execute('page_2');
    $view_result = $view->result;

    foreach ($view_result as $data) {
      $entity = $data->_entity;
      $entity->set('field_has_been_sent', TRUE);
      $entity->save();
    }
  }

  /**
   * Trigger sendgrid emailing.
   */
  public function sendDigest() {
    global $base_url;
    // Getting data from myemma.
    $emma_config = \Drupal::config('webform_myemma.settings');
    $emma = new JccClient($emma_config->get('account_id'), $emma_config->get('public_key'), $emma_config->get('private_key'));

    // Test digest : 13977604.
    $jcc_config = \Drupal::config('jcc_subscriptions.settings');
    $emma_group = $jcc_config->get('newslink_digest_group');

    $tempstore = \Drupal::service('tempstore.shared');
    $store = $tempstore->get('jcc_subscriptions');

    // Gathering emails to send emails to.
    $email_to_sendgrid = [];
    $id_to_sendgrid = [];
    $email_access_keys = [];
    $id_access_keys = [];

    $group_details = $emma->get_group_detail($emma_group);
    if ($group_details != NULL) {
      $users_ammount = $group_details->active_count;
      $loops = ceil($users_ammount / 500);

      for ($x = 0; $x < $loops; $x++) {
        $start = ($x * 500);
        $users_in_group = $emma->list_group_members($emma_group, 0, $start);
        foreach ($users_in_group as $user_group) {
          if (!in_array($user_group->email, $email_to_sendgrid, TRUE)) {
            array_push($email_to_sendgrid, $user_group->email);
            // Building array of ID's for opting out urls.
            array_push($id_to_sendgrid, $user_group->member_id);

            $email_key = user_password();
            array_push($email_access_keys, $email_key);
            $store->set('member_email_' . $user_group->email, $email_key);

            $id_key = user_password();
            array_push($id_access_keys, $id_key);
            $store->set('member_id_' . $user_group->member_id, $id_key);
          }
        }
      }
    }

    $log_message_sendgrid = 'Subscriptions --- SENDDIGEST() myEmma recipients: ' . count($id_access_keys);
    \Drupal::logger('jcc_subscriptions')->notice($log_message_sendgrid);

    $view_digest = views_embed_view('news_digest', 'default');
    $email_body = \Drupal::service('renderer')->render($view_digest);

    $log_message_sendgrid = 'Subscriptions --- SENDDIGEST() email view loaded with vars :' . $email_body . ' ---date--- ' . date("F j, Y") . ' ---base_url--- ' . $base_url;
    \Drupal::logger('jcc_subscriptions')->notice($log_message_sendgrid);

    $body = str_replace(
      [
        '%email_body%',
        '%today_date%',
        '%base_url%',
      ],
      [
        $email_body,
        date("F j, Y"),
        $base_url,
      ],
      '
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
                              <p align="center" style="font-size:11pt;font-family:Calibri,sans-serif;margin:0;">
                                <b><span style="color:#202020;font-size:25.5pt;font-family:Arial,sans-serif;">
                                  <a href="%base_url%" target="_blank" rel="noopener noreferrer" data-auth="NotApplicable">
                                    <span style="color:#0088CC;font-weight:normal;text-decoration:none;">
                                      <img data-imagetype="External" src="%base_url%/sites/default/files/newsroom/NewsroomDrupalBanner%20Blue.png" originalsrc="%base_url%/sites/default/files/newsroom/NewsroomDrupalBanner%20Blue.png" border="0" id="x_x__x0000_i1025" style="width:375px;">
                                    </span>
                                  </a></span>
                                </b>
                              </p>
                              </div>
                            <div style="margin-right:24pt;margin-left:24pt;">
                              <p style="font-size:11pt;font-family:Calibri,sans-serif;margin:0;">
                                <span style="color:#202020;font-size:10pt;font-family:Arial,sans-serif;">
                                  <a href="%base_url%" target="_blank" rel="noopener noreferrer" data-auth="NotApplicable">
                                    <span style="color:#0088CC;text-decoration:none;">See NewsLinks in Newsroom</span>
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
                                      <span style="font-size:16.5pt;">NewsLinks Digest</span>
                                    </h1>
                                    <h3>%today_date%</h3>
                                    <br>
                                    <div>%email_body%</div>
                                    <br>
                                    <p><a href="%base_url%/subscriptions/%member_email%/manage/%email_key%">manage your preferences</a><br>
                                    or <a href="%base_url%/subscriptions/%member_id%/delete-all/%id_key%">opt out</a> from all communications.</p>
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
          </table>
        '
    );

    // Getting from email.
    if (!empty(\Drupal::service('key.repository')->getKey('newsroom_sendgrid'))) {
      $sendgrid_conf = \Drupal::config('sendgrid_integration.settings')->get('test_defaults');
      $to = $sendgrid_conf['from_name'];
      $email = new Email();
      $email->setSmtpapiTos($email_to_sendgrid)
        ->setFrom($to)
        ->setFromName(\Drupal::config('system.site')->get('name'))
        ->setSubject('California Courts NewsLinks Digest - ' . date("F j, Y"))
        ->setText('California Courts NewsLinks Digest - ' . date("F j, Y"))
        ->setHtml($body)
        ->addSubstitution('%member_id%', $id_to_sendgrid)
        ->addSubstitution('%member_email%', $email_to_sendgrid)
        ->addSubstitution('%id_key%', $id_access_keys)
        ->addSubstitution('%email_key%', $email_access_keys)
        ->addHeader('X-Sent-Using', 'SendGrid-API')
        ->addHeader('X-Transport', 'web')
        ->setCategories(
          [
            'NewsLinks Digest',
            'NewsLinks Digest - ' . date("F j, Y"),
          ]
        );

      // Issue when simply calling $sendgrid->send($email);
      // fix from https://www.drupal.org/project/sendgrid_integration/issues/3041660#comment-13784755
      // Send an email using the template stored in SendGrid.
      $data = [];
      $data['email'] = $email;
      $data['email_ammount'] = count($email_to_sendgrid);

      $subscriptionQueue = \Drupal::queue('send_subscriptions_queue');
      if ($subscriptionQueue->numberOfItems() == 0) {
        $subscriptionQueue->createQueue();
        $subscriptionQueue->createItem($data);
        \Drupal::logger('jcc_subscriptions')->notice('Subscriptions --- Request to sendgrid queue worker sent.');
      }
      else {
        \Drupal::logger('jcc_subscriptions')->notice('Subscriptions --- ' . $subscriptionQueue->numberOfItems() . ' digest item(s) is(are) already being processed in the subscription queue worker.');
      }
    }
  }

}
