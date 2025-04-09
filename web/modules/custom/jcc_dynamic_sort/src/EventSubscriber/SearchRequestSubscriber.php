<?php

namespace Drupal\jcc_dynamic_sort\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;


/**
 * Subscriber to hide Alert nodes from anonymous visitors.
 */
class SearchRequestSubscriber implements EventSubscriberInterface {

//  protected $logger;

  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
//    $this->logger = $logger_factory->get('dynamic_sort');
    \Drupal::logger('dynamic_sort')->info("__construct");
  }

  public function onKernelRequest(RequestEvent $event) {
    \Drupal::logger('dynamic_sort')->info("onKernelRequest");

    $request = $event->getRequest();
    $path = $request->getPathInfo();

    // Check if it's the /search path.
    if ($path === '/search') {
      $keywords = $request->query->get('keywords');
//      $this->logger->info('Search triggered with keywords: @keywords', ['@keywords' => $keywords]);
      \Drupal::logger('dynamic_sort')->info("onKernelRequest, queried");
      // Insert your custom logic here. For example:
      // - Trigger batch processing
      // - Set a timestamp
      // - Update entity values (though saving entities on GET is not ideal)
    }
  }

  public static function getSubscribedEvents() {
    \Drupal::logger('dynamic_sort')->info("getSubscribedEvents");
    return [
      KernelEvents::REQUEST => ['onKernelRequest'],
    ];
  }

}
