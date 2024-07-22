<?php

namespace Drupal\jcc_elevated_rfp_solicitations\Controller;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for Requests (rfp) related pages.
 */
class JccElevatedRfpSolicitationRequestController {

  /**
   * Redirect from base request admin route to view the admin display.
   */
  public function redirectToActiveRequestsAdmin(): RedirectResponse {
    $route_provider = \Drupal::service('router.route_provider');
    $exists = count($route_provider->getRoutesByNames(['view.requests.requests_admin_active'])) === 1;
    if ($exists) {
      $url = Url::fromRoute('view.requests.requests_admin_active', [], ['absolute' => TRUE]);
      return new RedirectResponse($url->toString());
    }

    return new RedirectResponse('/');
  }

}
