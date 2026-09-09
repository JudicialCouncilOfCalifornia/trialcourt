<?php

namespace Drupal\jcc_subscriptions\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\jcc_messaging_center\Service\JccMessagingCenterMailService;
use Drupal\jcc_subscriptions\Services\JCCSubscriptionsDigestCron;
use SendGrid\Exception\SendgridException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes tasks for subscriptions module.
 *
 * @QueueWorker(
 *   id = "send_subscriptions_queue",
 *   title = @Translation("Subscriptions: Send Digest."),
 *   cron = {"time" = 90}
 * )
 */
final class SendSubscriptionsQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The messaging center mail service.
   *
   * @var \Drupal\jcc_messaging_center\Service\JccMessagingCenterMailService
   */
  protected $mailService;

  /**
   * Logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs the queue worker.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\jcc_messaging_center\Service\JccMessagingCenterMailService $mail_service
   *   The messaging center mail service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, JccMessagingCenterMailService $mail_service, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mailService = $mail_service;
    $this->logger = $logger_factory->get('jcc_subscriptions');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('jcc_messaging_center.mail_service'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!$this->isProcessable($data)) {
      // Returning normally lets the queue delete the unusable item.
      return;
    }

    try {
      $sent = $this->mailService->sendMail(
        $data['subject'],
        $data['body'],
        $data['recipients'],
        $data['access_keys'] ?? [],
        [
          'categories' => $data['categories'] ?? [],
          // Nobody is around to read a message during cron.
          'notify' => FALSE,
          // Needed to tell a transient failure from a permanent one.
          'rethrow' => TRUE,
        ]
      );
    }
    catch (SendgridException $e) {
      $code = (int) $e->getCode();
      if ($code !== 0 && $code < 500 && $code !== 429) {
        // SendGrid rejected the payload itself, so retrying it can never
        // succeed. Drop the item rather than retry it on every cron run.
        $this->logger->error('Subscriptions --- Dropping digest: SendGrid rejected it (HTTP @code): @message', [
          '@code' => $code,
          '@message' => $e->getMessage(),
        ]);
        return;
      }
      throw new SuspendQueueException('Subscriptions --- Transient SendGrid failure (HTTP ' . $code . '); digest will be retried: ' . $e->getMessage());
    }
    catch (\Throwable $e) {
      // On a connection failure the SendGrid client reads the status code off
      // a NULL response, which raises an Error rather than an exception. Cron
      // only catches exceptions, so an Error here would abort the whole cron
      // run instead of just this queue.
      throw new SuspendQueueException('Subscriptions --- SendGrid transport failure; digest will be retried: ' . $e->getMessage());
    }

    if (!$sent) {
      throw new SuspendQueueException('Subscriptions --- SendGrid did not accept the digest; it will be retried.');
    }

    $this->logger->notice('Subscriptions --- Digest sent to @count recipient(s).', [
      '@count' => $data['count'] ?? count($data['recipients']),
    ]);
  }

  /**
   * Checks whether a queue payload can be sent.
   *
   * @param mixed $data
   *   The queue item data.
   *
   * @return bool
   *   TRUE if the payload holds everything needed to send the digest.
   */
  protected function isProcessable($data) {
    if (!is_array($data)) {
      $this->logger->error('Subscriptions --- Dropping digest queue item: payload is @type, expected an array.', [
        '@type' => gettype($data),
      ]);
      return FALSE;
    }

    // Items queued before the SendGrid API migration carried a serialized
    // SendGrid\Email object, a class that no longer exists. Touching its
    // properties would fail, so recognise and drop those items instead.
    if (($data['version'] ?? 0) !== JCCSubscriptionsDigestCron::PAYLOAD_VERSION) {
      $this->logger->warning('Subscriptions --- Dropping digest queue item built by an older release; it targeted the removed SendGrid\Email API.');
      return FALSE;
    }

    foreach (['subject', 'body', 'recipients'] as $key) {
      if (empty($data[$key])) {
        $this->logger->error('Subscriptions --- Dropping malformed digest queue item: "@key" is missing.', [
          '@key' => $key,
        ]);
        return FALSE;
      }
    }

    if (!is_array($data['recipients'])) {
      $this->logger->error('Subscriptions --- Dropping malformed digest queue item: "recipients" is not an array.');
      return FALSE;
    }

    return TRUE;
  }

}
