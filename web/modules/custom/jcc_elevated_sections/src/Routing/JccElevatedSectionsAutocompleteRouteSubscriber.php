<?php

namespace Drupal\jcc_elevated_sections\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Routesubscriber for the Node with Section ID autocomplete matching service.
 */
class JccElevatedSectionsAutocompleteRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritDoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('system.entity_autocomplete')) {
      $route->setDefault('_controller', '\Drupal\jcc_elevated_sections\Controller\JccElevatedSectionsEntityAutocompleteController::handleAutocomplete');
    }
  }

}
