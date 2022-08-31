<?php

namespace Drupal\jcc_custom\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {


  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('protected_pages_list')) {
      $route->setDefault('_controller', '\Drupal\jcc_custom\Controller\ProtectedPagesController::protectedPagesList');
    }
  }

}
