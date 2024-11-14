<?php

namespace Drupal\jcc_tc2_roc_feature\EventSubscriber;

use Drupal\Component\Utility\Xss;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects bad calls to static resources for the hdro app.
 */
class JccRocEventSubscriber implements EventSubscriberInterface {

  /**
   * Checks if trying to load a static resource, and redirects to module source.
   */
  public function redirectOldRulesUrlsToNewRulesUrls(RequestEvent $event): void {

    // This is needed.
    if (!$event->isMasterRequest()) {
      return;
    }

    $request = $event->getRequest();
    $path = $request->getPathInfo();
    $query = $request->query->all();

    // If requesting the old coldfusion url.
    if ($path == '/cms/rules/index.cfm') {

      // Reset the base path to have no ".cfm".
      $path = '/cms/rules/index';
      $title = Xss::filter($query['title'] ?? '');
      $linkid = Xss::filter($query['linkid'] ?? '');

      if ($title) {
        $path .= '/' . $title;

        if ($linkid) {
          $path .= '/' . $linkid;
        }
      }

      // Redirect to the module based flag or node based flag.
      $redirect = new RedirectResponse($path, 301);
      $event->setResponse($redirect);
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {

    $events[KernelEvents::REQUEST][] = [
      'redirectOldRulesUrlsToNewRulesUrls',
      100,
    ];

    return $events;
  }

}
