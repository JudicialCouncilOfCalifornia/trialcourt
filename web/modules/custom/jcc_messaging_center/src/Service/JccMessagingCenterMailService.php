<?php

namespace Drupal\jcc_messaging_center\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\key\KeyRepositoryInterface;
use SendGrid\Client;
use SendGrid\Exception\SendgridException;
use SendGrid\Mail\Mail;
use SendGrid\Mail\MimeType;

/**
 * Provides SendGrid mail utilities for the messaging center.
 */
class JccMessagingCenterMailService {

  use StringTranslationTrait;

  /**
   * Maximum personalizations the SendGrid v3 mail/send endpoint accepts.
   */
  const PERSONALIZATION_LIMIT = 1000;

  /**
   * Key entity ID used when the site configuration does not name one.
   */
  const DEFAULT_KEY_ID = 'sendgrid';

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
   * Key repository.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs the mail service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\key\KeyRepositoryInterface $key_repository
   *   The key repository.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, KeyRepositoryInterface $key_repository, MessengerInterface $messenger, TranslationInterface $string_translation) {
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('sendgrid_message');
    $this->keyRepository = $key_repository;
    $this->messenger = $messenger;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Sends a SendGrid message.
   *
   * @param string $email_title
   *   The subject, also used as the plain text part of the message.
   * @param string $body
   *   The HTML body. Leave the per-recipient tokens %member_email% and
   *   %email_key% unreplaced so SendGrid substitutes them per recipient.
   * @param string|array $email_to_sendgrid
   *   A recipient address, or a list of recipient addresses.
   * @param string|array $email_access_keys
   *   An access key, or a list of access keys index aligned with
   *   $email_to_sendgrid.
   * @param array $options
   *   (optional) Additional settings:
   *   - key_id: the ID of the Key entity holding the API key. Defaults to the
   *     ID configured in sendgrid_integration.settings, then to 'sendgrid'.
   *   - categories: the SendGrid categories to tag the message with. Defaults
   *     to a pair built from $email_title.
   *   - notify: whether to report the outcome through the messenger. Defaults
   *     to TRUE; pass FALSE from cron and queue contexts, where there is no
   *     one to read the message.
   *   - rethrow: whether to let a SendgridException bubble up so the caller can
   *     tell a transient failure from a permanent one. Defaults to FALSE.
   *
   * @return bool
   *   TRUE if SendGrid accepted every request, FALSE otherwise.
   *
   * @throws \SendGrid\Exception\SendgridException
   *   When the send fails and $options['rethrow'] is TRUE.
   */
  public function sendMail($email_title, $body, $email_to_sendgrid, $email_access_keys, array $options = []) {
    $options += [
      'key_id' => NULL,
      'categories' => [],
      'notify' => TRUE,
      'rethrow' => FALSE,
    ];

    $key_id = $this->getKeyId($options['key_id']);
    if ($key_id === NULL) {
      $this->logger->error('No usable SendGrid key entity found; the message "@subject" was not sent.', [
        '@subject' => $email_title,
      ]);
      $this->report(FALSE, $options);
      return FALSE;
    }

    $defaults = $this->configFactory->get('sendgrid_integration.settings')->get('test_defaults') ?: [];
    $from = $defaults['from_name'] ?? '';
    if (empty($from)) {
      $this->logger->error('No from address configured in sendgrid_integration.settings; the message "@subject" was not sent.', [
        '@subject' => $email_title,
      ]);
      $this->report(FALSE, $options);
      return FALSE;
    }

    $recipients = array_values(is_array($email_to_sendgrid) ? $email_to_sendgrid : [$email_to_sendgrid]);
    $access_keys = array_values(is_array($email_access_keys) ? $email_access_keys : [$email_access_keys]);

    $client = new Client($this->keyRepository->getKey($key_id)->getKeyValue());
    $success = TRUE;

    if (count($recipients) > self::PERSONALIZATION_LIMIT) {
      // Callers that retry on failure should know the send is not atomic: a
      // later request failing does not undo the ones already accepted.
      $this->logger->warning('"@subject" is being split over @requests requests for @count recipients; a partial failure can duplicate the accepted batches on retry.', [
        '@subject' => $email_title,
        '@requests' => (int) ceil(count($recipients) / self::PERSONALIZATION_LIMIT),
        '@count' => count($recipients),
      ]);
    }

    // One personalization is created per recipient, and the endpoint rejects a
    // request carrying more than PERSONALIZATION_LIMIT of them, so split large
    // recipient lists over several requests. Preserving the keys keeps each
    // recipient aligned with its own access key.
    foreach (array_chunk($recipients, self::PERSONALIZATION_LIMIT, TRUE) as $chunk) {
      $this->logger->notice('Firing send event for "@subject" to @count recipient(s) using key @key.', [
        '@subject' => $email_title,
        '@count' => count($chunk),
        '@key' => $key_id,
      ]);

      try {
        $response = $client->send($this->buildMail($email_title, $body, $from, $chunk, $access_keys, $options));
        $success = ((int) $response->getCode() === 202) && $success;
      }
      catch (SendgridException $e) {
        $this->logger->error('SendGrid rejected "@subject" (HTTP @code): @message', [
          '@subject' => $email_title,
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ]);
        if ($options['rethrow']) {
          throw $e;
        }
        $success = FALSE;
      }
    }

    $this->report($success, $options);
    return $success;
  }

  /**
   * Builds the SendGrid message for one batch of recipients.
   *
   * @param string $email_title
   *   The subject, also used as the plain text part of the message.
   * @param string $body
   *   The HTML body.
   * @param string $from
   *   The from address.
   * @param array $recipients
   *   The recipient addresses of this batch, keyed by their position in the
   *   full recipient list.
   * @param array $access_keys
   *   The access keys of the full recipient list.
   * @param array $options
   *   The resolved options, as documented on ::sendMail().
   *
   * @return \SendGrid\Mail\Mail
   *   The message, with one personalization per recipient.
   */
  protected function buildMail($email_title, $body, $from, array $recipients, array $access_keys, array $options) {
    $email = new Mail();
    $email->setFrom($from, $this->configFactory->get('system.site')->get('name'));
    $email->setSubject($email_title);
    $email->addContent(MimeType::TEXT, $email_title);
    $email->addContent(MimeType::HTML, $body);
    $email->addGlobalHeader('X-Sent-Using', 'SendGrid-API');
    $email->addGlobalHeader('X-Transport', 'web');
    $email->addCategories($options['categories'] ?: [
      'Email Alert',
      'Email Alert - ' . $email_title,
    ]);

    // Add recipients and substitutions for each recipient.
    foreach ($recipients as $index => $recipient) {
      $email->addTo($recipient, NULL, [
        '%member_email%' => $recipient,
        '%email_key%' => $access_keys[$index] ?? '',
      ]);
    }

    return $email;
  }

  /**
   * Resolves the ID of the Key entity holding the API key.
   *
   * The key entity ID differs per site and is not in code, so fall back to the
   * ID that sendgrid_integration is configured with before guessing. Callers
   * can use this to check whether sending is configured at all before doing
   * expensive work.
   *
   * @param string|null $explicit
   *   (optional) A caller supplied key entity ID, which wins over the site
   *   configuration.
   *
   * @return string|null
   *   The first ID that resolves to a key holding a value, or NULL if none of
   *   the candidates does.
   */
  public function getKeyId($explicit = NULL) {
    $candidates = [
      $explicit,
      $this->configFactory->get('sendgrid_integration.settings')->get('apikey'),
      self::DEFAULT_KEY_ID,
    ];

    foreach (array_unique(array_filter($candidates)) as $candidate) {
      $key = $this->keyRepository->getKey($candidate);
      if ($key && !empty($key->getKeyValue())) {
        return $candidate;
      }
    }

    return NULL;
  }

  /**
   * Reports the outcome of a send.
   *
   * @param bool $success
   *   Whether SendGrid accepted the message.
   * @param array $options
   *   The resolved options, as documented on ::sendMail().
   */
  protected function report($success, array $options) {
    if ($success) {
      $this->logger->notice('SendGrid accepted the message.');
    }

    if (!$options['notify']) {
      return;
    }

    if ($success) {
      $this->messenger->addMessage($this->t('Email successfully sent'));
    }
    else {
      $this->messenger->addError($this->t('Email was not sent'));
    }
  }

}
