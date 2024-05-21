<?php

namespace Drupal\jcc_elevated_custom\Controller;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for Requests (rfp) related pages.
 */
class JccElevatedCustomRequestController {

  /**
   * Redirect from base request admin route to view the admin display.
   */
  public function redirectToActiveRequestsAdmin(): RedirectResponse {
    $url = Url::fromRoute('view.requests.requests_admin_active', [], ['absolute' => TRUE]);
    return new RedirectResponse($url->toString());
  }

}
