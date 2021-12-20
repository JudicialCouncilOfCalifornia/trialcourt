<?php

namespace Drupal\jcc_autocomplete_duplicates\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class AutocompleteRouteSubscriber extends RouteSubscriberBase {

  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('autocomplete_deluxe.autocomplete')){
      $route->setDefault('_controller', '\Drupal\jcc_autocomplete_duplicates\Controller\JccEntityAutocompleteController::handleAutocomplete');
    }
  }

}
