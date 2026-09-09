<?php

namespace Drupal\jcc_subscriptions\Services;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\views\Views;
use JudicialCouncil\Emma\JccClient;

/**
 * Queues and schedules the NewsLinks digest.
 */
class JCCSubscriptionsDigestCron {

  /**
   * The queue the digest is handed to.
   */
  const QUEUE_NAME = 'send_subscriptions_queue';

  /**
   * Number of members fetched per myEmma request.
   */
  const EMMA_PAGE_SIZE = 500;

  /**
   * Payload version, so the queue worker can reject older items.
   */
  const PAYLOAD_VERSION = 2;

  /**
   * The ID of the service that sends the mail.
   */
  const MAILER_SERVICE = 'jcc_sendgrid_mail.mailer';

  /**
   * The HTML wrapper of the digest email.
   */
  const BODY_TEMPLATE = '
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
                                    or <a href="%base_url%/subscriptions/%member_email%/delete-all/%email_key%">opt out</a> from all communications.</p>
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
        ';

  /**
   * State service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

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
   * Queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Shared tempstore factory.
   *
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Password generator, used to mint per-recipient access keys.
   *
   * @var \Drupal\Core\Password\PasswordGeneratorInterface
   */
  protected $passwordGenerator;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Cached mail service, resolved on demand by ::mailService().
   *
   * @var \Drupal\jcc_sendgrid_mail\JccSendGridMailer
   */
  protected $mailService;

  /**
   * Constructs the digest cron service.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $temp_store_factory
   *   The shared tempstore factory.
   * @param \Drupal\Core\Password\PasswordGeneratorInterface $password_generator
   *   The password generator.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(StateInterface $state, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, QueueFactory $queue_factory, RendererInterface $renderer, SharedTempStoreFactory $temp_store_factory, PasswordGeneratorInterface $password_generator, TimeInterface $time) {
    $this->state = $state;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('jcc_subscriptions');
    $this->queueFactory = $queue_factory;
    $this->renderer = $renderer;
    $this->tempStoreFactory = $temp_store_factory;
    $this->passwordGenerator = $password_generator;
    $this->time = $time;
  }

  /**
   * Action on cron run.
   */
  public function cron() {
    $now = $this->time->getRequestTime();
    $jcc_config = $this->configFactory->get('jcc_subscriptions.settings');

    if ($jcc_config->get('newslink_digest_debug')) {
      $this->logger->notice('Subscriptions --- Newslink digest debug enabled.');
      $this->queueTasks();
    }

    if ($this->shouldRun($now, $jcc_config->get('newslink_digest_time'))) {
      // Checks if there is any news item to send.
      $view = Views::getView('news_digest');
      $view->get_total_rows = TRUE;
      $view->execute('default');
      $rows = $view->total_rows;

      $this->state->set('jcc_subscriptions.last_cron', $now);

      if ($rows != 0) {
        $this->queueTasks();
        $this->logger->notice('Subscriptions --- Newslink digest ran with @rows results, at @now.', [
          '@rows' => $rows,
          '@now' => $now,
        ]);
      }
      else {
        $this->logger->notice('No new newslink item are queued for email today.');
      }
    }
  }

  /**
   * Test if cron should run.
   *
   * @param int $now
   *   The current request time.
   * @param string $scheduled
   *   The time of day the digest is scheduled for, as H:i.
   *
   * @return bool
   *   TRUE if the digest is due.
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

    $this->logger->notice('Subscriptions --- Last JCC_subs cron: @last --- Next: @next', [
      '@last' => date('m/d/Y H:i:s', $last->getTimestamp()),
      '@next' => date('m/d/Y H:i:s', $next->getTimestamp()),
    ]);

    return ($next->getTimestamp() <= $now) && (time() >= strtotime($scheduled));
  }

  /**
   * Add task to the queue.
   */
  public function queueTasks() {
    $this->logger->notice('Subscriptions --- QUEUETASKS() START');

    // Only flag the news items once the digest carrying them is queued,
    // otherwise they would be dropped from every future digest without an
    // email ever going out.
    if ($this->sendDigest()) {
      $this->flagNewsItems();
    }

    $this->logger->notice('Subscriptions --- QUEUETASKS() END');
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
   * Returns the service that sends the mail, resolving it on first use.
   *
   * The jcc_sendgrid_mail module is a declared dependency, so it is resolved
   * on demand rather than injected: a constructor argument would make the
   * container fail to compile in the window between a code deploy and the
   * config import that installs the module.
   *
   * @return \Drupal\jcc_sendgrid_mail\JccSendGridMailer
   *   The mail service.
   */
  protected function mailService() {
    if ($this->mailService === NULL) {
      $this->mailService = \Drupal::service(self::MAILER_SERVICE);
    }

    return $this->mailService;
  }

  /**
   * Queue the digest for delivery.
   *
   * The message itself is sent by
   * \Drupal\jcc_subscriptions\Plugin\QueueWorker\SendSubscriptionsQueueWorker,
   * so the queue item must hold plain, serializable data only.
   *
   * @return bool
   *   TRUE if a digest was added to the queue.
   */
  public function sendDigest() {
    $queue = $this->queueFactory->get(self::QUEUE_NAME);

    // Deliberate duplicate-send guard, kept from the original implementation.
    if ($queue->numberOfItems() != 0) {
      $this->logger->notice('Subscriptions --- @count digest item(s) is(are) already being processed in the subscription queue worker.', [
        '@count' => $queue->numberOfItems(),
      ]);
      return FALSE;
    }

    // Without a usable API key the worker could never send this item, and
    // would keep retrying it on every cron run.
    if ($this->mailService()->getKeyId() === NULL) {
      $this->logger->error('Subscriptions --- No usable SendGrid key entity; digest not queued.');
      return FALSE;
    }

    $members = $this->collectGroupMembers();
    $this->logger->notice('Subscriptions --- SENDDIGEST() myEmma recipients: @count', [
      '@count' => count($members),
    ]);

    if (empty($members)) {
      $this->logger->warning('Subscriptions --- No myEmma recipients resolved; digest not queued.');
      return FALSE;
    }

    $date = date('F j, Y', $this->time->getRequestTime());

    $queue->createQueue();
    $queue->createItem([
      'version' => self::PAYLOAD_VERSION,
      'subject' => 'California Courts NewsLinks Digest - ' . $date,
      'body' => $this->buildDigestBody($date),
      'categories' => [
        'NewsLinks Digest',
        'NewsLinks Digest - ' . $date,
      ],
      'recipients' => array_column($members, 'email'),
      'access_keys' => array_column($members, 'email_key'),
      'count' => count($members),
    ]);

    $this->logger->notice('Subscriptions --- Digest queued for @count recipient(s).', [
      '@count' => count($members),
    ]);

    return TRUE;
  }

  /**
   * Collects the members of the configured myEmma digest group.
   *
   * Each member gets a freshly minted access key, stored in the shared
   * tempstore so the manage and opt-out forms can validate it.
   *
   * @return array
   *   A list of records with an 'email' and an 'email_key' key.
   */
  protected function collectGroupMembers() {
    $emma_config = $this->configFactory->get('webform_myemma.settings');
    if (!$emma_config->get('account_id')) {
      return [];
    }

    $emma = new JccClient($emma_config->get('account_id'), $emma_config->get('public_key'), $emma_config->get('private_key'));
    $emma_group = $this->configFactory->get('jcc_subscriptions.settings')->get('newslink_digest_group');

    $group_details = $emma->get_group_detail($emma_group);
    if (empty($group_details->active_count)) {
      return [];
    }

    $store = $this->tempStoreFactory->get('jcc_subscriptions');
    $members = [];
    $seen = [];
    $loops = ceil($group_details->active_count / self::EMMA_PAGE_SIZE);

    for ($x = 0; $x < $loops; $x++) {
      $users_in_group = $emma->list_group_members($emma_group, 0, $x * self::EMMA_PAGE_SIZE) ?: [];
      foreach ($users_in_group as $user_group) {
        if (empty($user_group->email) || isset($seen[$user_group->email])) {
          continue;
        }
        $seen[$user_group->email] = TRUE;

        $email_key = $this->passwordGenerator->generate();
        $store->set('member_email_' . $user_group->email, $email_key);

        $members[] = [
          'email' => $user_group->email,
          'email_key' => $email_key,
        ];
      }
    }

    return $members;
  }

  /**
   * Renders the digest body.
   *
   * @param string $date
   *   The formatted digest date.
   *
   * @return string
   *   The HTML body, with the per-recipient tokens left in place for SendGrid
   *   to substitute.
   */
  protected function buildDigestBody($date) {
    $view_digest = views_embed_view('news_digest', 'default');

    // views_embed_view() returns a render array, and Renderer::render() needs a
    // render context, which cron does not provide.
    $renderer = $this->renderer;
    $email_body = (string) $renderer->executeInRenderContext(new RenderContext(), function () use (&$view_digest, $renderer) {
      return $renderer->render($view_digest);
    });

    return str_replace(
      [
        '%email_body%',
        '%today_date%',
        '%base_url%',
      ],
      [
        $email_body,
        $date,
        $this->baseUrl(),
      ],
      self::BODY_TEMPLATE
    );
  }

  /**
   * Returns the base URL to build absolute links with.
   *
   * @return string
   *   The base URL, without a trailing slash.
   */
  protected function baseUrl() {
    global $base_url;

    return rtrim($base_url, '/');
  }

}
