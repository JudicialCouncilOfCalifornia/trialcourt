<?php

namespace Drupal\jcc_site_lockdown\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RedirectDestination;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\State\StateInterface;
use Drupal\openid_connect\OpenIDConnectClaims;
use Drupal\openid_connect\OpenIDConnectSession;
use Drupal\openid_connect\Plugin\OpenIDConnectClientManager;
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
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * A policy evaluating to static::DENY when the kill switch was triggered.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $pageCacheKillSwitch;

  /**
   * The OpenID Connect session service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectSession
   */
  protected $session;

  /**
   * Drupal\openid_connect\Plugin\OpenIDConnectClientManager definition.
   *
   * @var \Drupal\openid_connect\Plugin\OpenIDConnectClientManager
   */
  protected $pluginManager;

  /**
   * The OpenID Connect claims.
   *
   * @var \Drupal\openid_connect\OpenIDConnectClaims
   */
  protected $claims;

  /**
   * The config factory (for editable config).
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The state store.
   *
   * @var Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

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
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $pageCacheKillSwitch
   *   The cache kill switch service.
   * @param \Drupal\openid_connect\OpenIDConnectSession $session
   *   The OpenID Connect session service.
   * @param \Drupal\openid_connect\Plugin\OpenIDConnectClientManager $plugin_manager
   *   The plugin manager.
   * @param \Drupal\openid_connect\OpenIDConnectClaims $claims
   *   The OpenID Connect claims.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory for config.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state store.
   */
  public function __construct(
    AliasManager $aliasManager,
    AccountProxy $currentUser,
    CurrentPathStack $currentPathStack,
    RedirectDestination $destination,
    RequestStack $requestStack,
    CurrentRouteMatch $current_route_match,
    KillSwitch $pageCacheKillSwitch,
    OpenIDConnectSession $session,
    OpenIDConnectClientManager $plugin_manager,
    OpenIDConnectClaims $claims,
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler,
    StateInterface $state) {

    $this->aliasManager = $aliasManager;
    $this->currentUser = $currentUser;
    $this->currentPath = $currentPathStack;
    $this->destination = $destination;
    $this->requestStack = $requestStack;
    $this->currentRouteMatch = $current_route_match;
    $this->pageCacheKillSwitch = $pageCacheKillSwitch;
    $this->pluginManager = $plugin_manager;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->session = $session;
    $this->claims = $claims;
    $this->state = $state;
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
    if (in_array($current_path, [
      '/user',
      '/user/login',
      '/system/403',
      '/user/password',
    ])) {
      return;
    }

    // Check allowed routes for OpenID.
    $route_name = $this->currentRouteMatch->getRouteName();
    if (in_array($route_name, [
      'openid_connect.redirect_controller_redirect',
      'openid_connect_windows_aad.sso',
    ])) {
      return;
    }

    // Check if OpenID Connect Azure Entra is enabled.
    // By default, redirecting automatically to OpenID login is enabled.
    // To disable the auto-redirect, set 'azure_disable_redirect' to TRUE.
    // The command: drush state:set azure_disable_redirect TRUE.
    $disable_auth_redirect = $this->state->get('azure_disable_redirect', FALSE);
    if ((!$disable_auth_redirect) && $this->moduleHandler->moduleExists('openid_connect_windows_aad')) {
      $event->setResponse($this->redirectToOpenIdLogin());
    }
    else {
      $event->setResponse($this->sendAccessDenied());
    }
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
    $response->setStatusCode(Response::HTTP_FORBIDDEN);
    return $response;
  }

  /**
   * Redirect to OpenID login.
   */
  public function redirectToOpenIdLogin() {
    $this->pageCacheKillSwitch->trigger();
    $this->session->saveDestination();
    $client_name = 'windows_aad';

    $configuration = $this->configFactory
      ->get('openid_connect.settings.' . $client_name)
      ->get('settings');

    /** @var \Drupal\openid_connect\Plugin\OpenIDConnectClientInterface $client */
    $client = $this->pluginManager->createInstance(
      $client_name,
      $configuration
    );
    $scopes = $this->claims->getScopes($client);
    $_SESSION['openid_connect_op'] = 'login';
    $response = $client->authorize($scopes);
    return $response;
  }

}
