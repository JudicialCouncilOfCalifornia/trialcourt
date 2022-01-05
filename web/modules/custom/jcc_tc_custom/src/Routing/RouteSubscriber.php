<?php

namespace Drupal\jcc_tc_custom\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * See Webform tags.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.webform.canonical')) {
      $options = $route->getOptions();
      $options['tags'] = ['webform_canonical'];
      $route->setOptions($options);
    }
  }

}
