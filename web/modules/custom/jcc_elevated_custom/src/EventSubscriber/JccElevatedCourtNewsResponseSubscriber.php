<?php

namespace Drupal\jcc_elevated_custom\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class JccElevatedCourtNewsResponseSubscriber alters the response for CNUs.
 */
class JccElevatedCourtNewsResponseSubscriber implements EventSubscriberInterface {

  /**
   * Alter the controller output of Court News Updates for JRN.
   */
  public function onResponse(ResponseEvent $event) {

    if ($node = $this->getEntity($event)) {
      if ($node instanceof Node) {
        if ($node->bundle() == 'news') {
          // If the node is a Court News Update, alter the response.
          if ($node->field_news_type->entity 
              && $node->field_news_type->entity->getName() == 'Court News Update') {
            $response = new Response();
            $response->setContent($node->get('body')->value);
            $event->setResponse($response);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(ResponseEvent $event) {
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

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onResponse'];
    return $events;
  }

}
