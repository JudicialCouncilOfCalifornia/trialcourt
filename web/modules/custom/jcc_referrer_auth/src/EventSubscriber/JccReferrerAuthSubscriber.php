<?php

namespace Drupal\jcc_referrer_auth\EventSubscriber;

use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RedirectDestination;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\path_alias\AliasManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects user to an Access Denied page if not trusted.
 */
class JccReferrerAuthSubscriber implements EventSubscriberInterface {

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManager
   */
  protected $aliasManager;

  /**
   * The account proxy service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The current path stack service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestination
   */
  protected $destination;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * A policy evaluating to static::DENY when the kill switch was triggered.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $pageCacheKillSwitch;

  /**
   * The tempstore service.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStore;

  /**
   * Constructs a new JccReferrerAuthSubscriber.
   *
   * @param \Drupal\path_alias\AliasManager $aliasManager
   *   The path alias manager.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   The account proxy service.
   * @param \Drupal\Core\Path\CurrentPathStack $currentPathStack
   *   The current path stack service.
   * @param \Drupal\Core\Routing\RedirectDestination $destination
   *   The redirect destination service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $pageCacheKillSwitch
   *   The cache kill switch service.
   * @param \Drupal\user\PrivateTempStoreFactory $tempStore
   *   The tempStore service.
   */
  public function __construct(
    AliasManager $aliasManager,
    AccountProxy $currentUser,
    CurrentPathStack $currentPathStack,
    RedirectDestination $destination,
    RequestStack $requestStack,
    KillSwitch $pageCacheKillSwitch,
    PrivateTempStoreFactory $tempStore) {

    $this->aliasManager = $aliasManager;
    $this->currentUser = $currentUser;
    $this->currentPath = $currentPathStack;
    $this->destination = $destination;
    $this->requestStack = $requestStack;
    $this->pageCacheKillSwitch = $pageCacheKillSwitch;
    $this->tempStore = $tempStore->get("jcc_referrer_auth");
  }

  /**
   * Redirects user to protected page login screen.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function checkReferrerAccess(ResponseEvent $event) {

    // Allow access to all pages if user is logged in.
    if ($this->currentUser->isAuthenticated()) {
      return;
    }

    // Allow access to these specified pages anytime.
    $current_path = $this->aliasManager->getAliasByPath($this->currentPath->getPath());
    if (in_array($current_path, [
      '/user',
      '/user/login',
      '/system/403',
      '/system/404',
      '/user/password',
      '/access-denied',
    ])) {
      return;
    }

    // Prevent any page from getting cached.
    $this->pageCacheKillSwitch->trigger();

    // Allow access if the referrer is trusted.
    if ($this->tempStore->get('valid_user')) {
      return;
    }
    else {
      $referrer = $this->requestStack->getCurrentRequest()->server->get('HTTP_REFERER');
      $needles = [
        'lndo.site',
        'courts.ca.gov',
        'jud.ca.gov',
        'live-jcc-courts.pantheonsite.io',
      ];
      if (array_reduce($needles, fn($a, $n) => $a || str_contains($referrer, $n), FALSE)) {
        $this->tempStore->set('valid_user', TRUE);
        return;
      }
    }

    $this->sendAccessDenied();
    $response = new Response();
    $response->setStatusCode(Response::HTTP_NO_CONTENT);
    $event->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['checkReferrerAccess'];
    return $events;
  }

  /**
   * Send Access Denied for invalid users.
   */
  public function sendAccessDenied() {
    $this->pageCacheKillSwitch->trigger();
    $response = new RedirectResponse('/access-denied');
    $response->send();
  }

}
