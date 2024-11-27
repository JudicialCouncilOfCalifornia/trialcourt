<?php

namespace Drupal\jcc_custom\EventSubscriber;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;

/**
 * Subscriber to hide unpublished nodes from anonymous visitors.
 *
 * Nodeaccess and content moderation aren't playing properly and this event
 * subscriber will help fill in that missing gap of access checking. This issue
 * is being addressed in the contrib module, so this may not be needed in the
 * future.
 */
class JccCustomAnonymousDraftRedirectSubscriber implements EventSubscriberInterface {

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
   * Content moderation information.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              ModuleHandlerInterface $moduleHandler,
                              AccountInterface $current_user,
                              ModerationInformationInterface $moderation_information
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $moduleHandler;
    $this->currentUser = $current_user;
    $this->moderationInfo = $moderation_information;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('content_moderation.moderation_information'),
    );
  }

  /**
   * Get subscriber events.
   */
  public static function getSubscribedEvents(): array {
    $events['kernel.request'] = ['onRequest', 100];
    $events['kernel.response'] = ['onResponse', 100];
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
   * A method to be called whenever a kernel.response event is dispatched.
   *
   * Like the onRequest event, it invokes a rabbit hole behavior on an entity in
   * the request if possible. Unlike the onRequest event, it also passes in a
   * response.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event triggered by the response.
   */
  public function onResponse(KernelEvent $event): void {
    $this->processEvent($event);
  }

  /**
   * Process to hide unpublished, content moderated nodes from anonymous view.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event to process.
   */
  private function processEvent(KernelEvent $event): void {
    if ($node = $this->getEntity($event)) {
      if ($node instanceof Node && $this->currentUser->isAnonymous()) {

        // Ignore if there is an access_unpublished key in the url.
        $config = \Drupal::config('access_unpublished.settings');
        $access_unpublished_key = \Drupal::request()->get($config->get('hash_key'));
        if (!$access_unpublished_key) {
          $deny_access = FALSE;
          $current_moderation_state = NULL;

          // Gather some information about the current nodes primary status and
          // moderation state. Primary status (or default status) refers to  if
          // there is a published version of the node (ie, one of the revisions
          // was published at some point in time).
          $current_status = $node->isPublished();
          $node_manager = $this->entityTypeManager->getStorage('node');
          $latest_revision_vid = $node_manager->getLatestRevisionId($node->id());
          $latest_revision = $node_manager->loadRevision($latest_revision_vid);
          $latest_content_moderation_state = $latest_revision->moderation_state->value ?? '';

          $entity_workflow = $this->moderationInfo->getWorkflowForEntity($latest_revision);
          if ($entity_workflow) {
            $current_moderation_state = $entity_workflow->getTypePlugin()
              ->getState($latest_content_moderation_state)
              ->id();
          }

          // If the current status of the node is "No published state"...
          if (!$current_status) {
            $deny_access = TRUE;
          }

          // If the current moderation state of latest revision is "archived"...
          if ($current_moderation_state == 'archived') {
            $deny_access = TRUE;
          }

          // If either scenario above is true, we redirect to the access denied
          // page. This is our backup to a faulty integration of nodeaccess and
          // content moderation. Contrib issues are in the works to fix this, so
          // this eventSubscriber may not be needed in the future.
          if ($deny_access) {
            $url = Url::fromRoute('system.403');
            $response = new TrustedRedirectResponse($url->toString(), '301');
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

          // Return the entity we are viewing. We will check if it is a node in
          // the other methods above.
          return $entity;
        }
      }
    }

    return FALSE;
  }

}
