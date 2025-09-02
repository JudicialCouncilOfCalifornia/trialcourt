<?php

namespace Drupal\jcc_referrer_auth\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Referrer Auth.
 */
class JccReferrerAuthController extends ControllerBase {

  /**
   * Returns the Access Denied page.
   */
  public function accessDenied() {
    return [
      '#markup' => '<div class="container box"><h1>Restricted Area</h1> <p>For access to the racial justice toolkit, please log into the <a href="https://jrn.courts.ca.gov">Judicial Resources Network</a>.</p></div>',
      '#allowed_tags' => ['div', 'h1', 'p', 'a'],
    ];
  }

}
