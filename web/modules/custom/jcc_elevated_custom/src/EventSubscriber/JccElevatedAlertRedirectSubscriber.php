<?php

namespace Drupal\jcc_elevated_custom\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;

/**
 * Subscriber to hide Alert nodes from anonymous visitors.
 */
class JccElevatedAlertRedirectSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  public $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $moduleHandler, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $moduleHandler;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
    );
  }

  /**
   * Get subscriber events.
   */
  public static function getSubscribedEvents() {
    $events['kernel.request'] = ['onRequest', 99];
    $events['kernel.response'] = ['onResponse'];
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
  public function onRequest(KernelEvent $event) {
    // This is needed.
    if (!$event->isMasterRequest()) {
      return;
    }

    $this->processEvent($event);
  }

  /**
   * A method to be called whenever a kernel.response event is dispatched.
   *
   * Like the onRequest event, it invokes a rabbit hole behavior on an entity in
   * the request if possible. Unlike the onRequest event, it also passes in a
   * response.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event triggered by the response.
   */
  public function onResponse(KernelEvent $event) {
    $this->processEvent($event);
  }

  /**
   * Process events to hide alert nodes from anonymous view.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event to process.
   */
  private function processEvent(KernelEvent $event) {
    if ($node = $this->getEntity($event)) {
      if ($node instanceof Node) {

        if ($node->bundle() == 'alert') {
          if ($this->currentUser->isAnonymous()) {
            $response = new TrustedRedirectResponse('/', '301');
            $response->addCacheableDependency($node);
            $event->setResponse($response);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(KernelEvent $event) {
    $request = $event->getRequest();

    // Don't process events with HTTP exceptions - those have either been thrown
    // by us or have nothing to do with our needs.
    if ($request->get('exception') != NULL) {
      return FALSE;
    }

    // Get the route from the request.
    if ($route = $request->get('_route')) {
      // Only continue if the request route is a canonical entity.
      if (preg_match('/^entity\.(.+)\.canonical$/', $route, $matches)) {
        $entity = $request->get($matches[1]);
        if ($entity instanceof EntityInterface) {
          return $entity;
        }
      }
    }

    return FALSE;
  }

}
