<?php

namespace Drupal\jcc_custom\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -1025];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $routes = $collection->all();
    foreach ($routes as $route_name => $route) {
      switch ($route_name) {
        case 'protected_pages_list':
          $route->setDefault('_controller', '\Drupal\jcc_custom\Controller\ProtectedPagesController::protectedPagesList');
          break;
        case 'news_release_link_archiving':
          $route->setDefault('_controller', '\Drupal\jcc_custom\Controller\JccNewsArchiveController::performNewsArchiving');
          break;
        case 'entity.menu.collection':
        case 'entity.menu.edit_form':
        case 'entity.menu.add_link_form':
        case 'menu_ui.link_edit':
        case 'menu_ui.link_reset':
        case 'entity.menu_link_content.canonical':
        case 'entity.menu_link_content.delete_form':
        case 'entity.menu_link_content.content_translation_overview':
        case 'entity.menu_link_content.content_translation_add':
        case 'entity.menu_link_content.content_translation_edit':
        case 'entity.menu_link_content.content_translation_delete':

          // Restrict menu management toolbar for elevated sites.
          // Translators edit requests under /admin/tmgmt/jobs.
          if (jcc_custom_is_elevated_site()) {
            $route->setRequirement('_role', 'administrator');
          }
          break;
      }
    }
  }

}
