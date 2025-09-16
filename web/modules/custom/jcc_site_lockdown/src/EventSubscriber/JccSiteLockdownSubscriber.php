<?php

namespace Drupal\jcc_site_lockdown\EventSubscriber;

use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RedirectDestination;
use Drupal\Core\Session\AccountProxy;
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
class JccSiteLockdownSubscriber implements EventSubscriberInterface {

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
   */
  public function __construct(
    AliasManager $aliasManager,
    AccountProxy $currentUser,
    CurrentPathStack $currentPathStack,
    RedirectDestination $destination,
    RequestStack $requestStack,
    KillSwitch $pageCacheKillSwitch) {

    $this->aliasManager = $aliasManager;
    $this->currentUser = $currentUser;
    $this->currentPath = $currentPathStack;
    $this->destination = $destination;
    $this->requestStack = $requestStack;
    $this->pageCacheKillSwitch = $pageCacheKillSwitch;
  }

  /**
   * Redirects user to protected page login screen.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function checkAccess(ResponseEvent $event) {

    // Allow access to all pages if user is logged in.
    if ($this->currentUser->isAuthenticated()) {
      return;
    }

    // Allow access to these specified pages anytime.
    $current_path = $this->aliasManager->getAliasByPath($this->currentPath->getPath());
    $user_reset_pattern = ['/^/user/reset.*$/'];
    if (in_array($current_path, [
      '/user',
      '/user/login',
      '/system/403',
      '/user/password',
    ]) || preg_grep($user_reset_pattern, [$current_path])) {
      return;
    }

    $this->sendAccessDenied();
    $response = new Response();
    $response->setStatusCode(Response::HTTP_FORBIDDEN);
    $event->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['checkAccess'];
    return $events;
  }

  /**
   * Send Access Denied for invalid users.
   */
  public function sendAccessDenied() {
    $this->pageCacheKillSwitch->trigger();
    $response = new RedirectResponse('/user/login', 302);
    $response->send();
  }

}
