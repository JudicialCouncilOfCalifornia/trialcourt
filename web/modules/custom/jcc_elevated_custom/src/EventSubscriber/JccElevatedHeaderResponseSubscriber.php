<?php

namespace Drupal\jcc_elevated_custom\EventSubscriber;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber to customize response headers.
 */
class JccElevatedHeaderResponseSubscriber implements EventSubscriberInterface {

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * Extends the response.
   */
  public function onResponse(ResponseEvent $event) {
    // Add X-Robots-Tag header response if opted.
    if (theme_get_setting('block_search_engine_indexing')) {
      $response = $event->getResponse();
      $response->headers->set('X-Robots-Tag', 'none');
      $event->setResponse($response);
    }
  }

  /**
   * Get subscriber events.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onResponse'];
    return $events;
  }

}
