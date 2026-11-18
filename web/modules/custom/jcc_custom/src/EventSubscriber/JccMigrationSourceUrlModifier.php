<?php

namespace Drupal\jcc_custom\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Plugin\MigrationInterface;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds the jobs cache buster to the HTTP request, not to stored configuration.
 */
final class JccMigrationSourceUrlModifier implements EventSubscriberInterface {

  /**
   * URLs belonging to the active jobs migration.
   */
  private array $urls = [];

  /**
   * One validated source snapshot for counting, syncing and importing.
   *
   * @var \Psr\Http\Message\ResponseInterface[]
   */
  private array $responses = [];

  /**
   * Cache buster for the active execution.
   */
  private int $timestamp = 0;

  /**
   * The migration owning the current HTTP context.
   */
  private ?string $migrationId = NULL;

  public function __construct(private TimeInterface $time) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MigrateEvents::PRE_IMPORT => ['onPreImport', 100],
      MigrateEvents::POST_IMPORT => ['reset', -100],
    ];
  }

  /**
   * Activates the request modifier before a scheduled import.
   */
  public function onPreImport(MigrateImportEvent $event): void {
    $this->prepare($event->getMigration());
  }

  /**
   * Activates it before a batch counts its source as well as before import.
   */
  public function prepare(MigrationInterface $migration): void {
    if (!in_array($migration->id(), ['courts_node_job', 'appellate_node_job'], TRUE)) {
      $this->reset();
      return;
    }
    $source = $migration->getSourceConfiguration();
    $urls = [];
    foreach ((array) ($source['urls'] ?? []) as $url) {
      $uri = new Uri($url);
      if (in_array($uri->getScheme(), ['http', 'https'], TRUE)) {
        $urls[(string) $uri->withFragment('')] = TRUE;
      }
    }
    if ($this->migrationId === $migration->id() && $this->urls === $urls && (!isset($source['jcc_job_source_timestamp']) || (int) $source['jcc_job_source_timestamp'] === $this->timestamp)) {
      return;
    }
    $this->responses = [];
    $this->urls = $urls;
    $this->migrationId = $migration->id();
    $this->timestamp = (int) ($source['jcc_job_source_timestamp'] ?? $this->time->getCurrentTime());
  }

  /**
   * Clears the execution context, including after a failed batch.
   */
  public function reset(): void {
    $this->urls = [];
    $this->responses = [];
    $this->migrationId = NULL;
  }

  /**
   * Returns the Guzzle middleware used by Drupal's HTTP client.
   */
  public function __invoke(): callable {
    return function (callable $handler): callable {
      return function (RequestInterface $request, array $options) use ($handler) {
        $uri = $request->getUri();
        if ($request->getMethod() !== 'GET' || !isset($this->urls[(string) $uri->withFragment('')])) {
          return $handler($request, $options);
        }
        $uri = Uri::withQueryValue($uri, 'timestamp', $this->timestamp);
        $request = $request->withUri($uri);
        $key = (string) $uri;
        if (isset($this->responses[$key])) {
          $response = $this->responses[$key];
          return Create::promiseFor($response->withBody(Utils::streamFor((string) $response->getBody())));
        }
        return $handler($request, $options)->then(function (ResponseInterface $response) use ($key) {
          if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $this->responses[$key] = $response->withBody(Utils::streamFor((string) $response->getBody()));
          }
          return $response;
        });
      };
    };
  }

}
