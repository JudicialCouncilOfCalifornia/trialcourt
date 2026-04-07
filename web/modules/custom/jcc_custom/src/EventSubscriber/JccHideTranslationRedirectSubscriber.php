<?php

namespace Drupal\jcc_custom\EventSubscriber;

use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\CacheableRedirectResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;

/**
 * Subscriber to manage translations.
 */
class JccHideTranslationRedirectSubscriber implements EventSubscriberInterface {
  /**
   * The route match interface.
   *
   * @var Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The account interface.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The configuration interface.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(RouteMatchInterface $current_route_match, AccountInterface $current_user, ConfigFactoryInterface $config_factory) {
    $this->currentRouteMatch = $current_route_match;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['kernel.request'][] = ['onRequest', 30];
    return $events;
  }

  /**
   * A method to be called whenever a "kernel.request" event is dispatched.
   *
   * It invokes a rabbit hole behavior on an entity in the request if
   * applicable.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event triggered by the request.
   */
  public function onRequest(KernelEvent $event): void {
    // This is needed.
    if (!$event->isMasterRequest()) {
      return;
    }

    $this->processEvent($event);
  }

  /**
   * Management for translation availability.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event to process.
   */
  public function processEvent(KernelEvent $event) {
    // If translations are hidden, redirect anonymous to original language.
    $node = $this->currentRouteMatch->getParameter('node');
    if ($node instanceof NodeInterface) {
      if ($this->currentUser->isAnonymous()) {
        $theme = $this->configFactory->get('system.theme')->get('default');
        $config = $this->configFactory->getEditable($theme . '.settings');
        if ($config->get('hide_translation') && !$node->isDefaultTranslation()) {
          $untranslated_url = '/';
          $untranslated_node = $node->getUntranslated();
          if ($untranslated_node && $untranslated_node->toUrl()) {
            $untranslated_url = $untranslated_node->toUrl()->toString();
          }
          $response = new CacheableRedirectResponse($untranslated_url);
          $event->setResponse($response);
        }
      }
    }
  }

}
