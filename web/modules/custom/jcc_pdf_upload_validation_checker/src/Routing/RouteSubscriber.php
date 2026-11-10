<?php

namespace Drupal\jcc_pdf_upload_validation_checker\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters the PDF Validation overview view's route to add a config-based gate.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('view.pdf_validation_overview.page_1');
    if (!$route) {
      return;
    }

    $route->setRequirement(
      '_custom_access',
      '\Drupal\jcc_pdf_upload_validation_checker\Access\PdfValidationOverviewAccessCheck::access'
    );
  }

}
