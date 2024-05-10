<?php

namespace Drupal\jcc_tc2_roc_feature\Controller;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for Rules of Court.
 */
class JccRocController {

  /**
   * Redirect from base rule admin route to view document admin display.
   */
  public function redirectToRuleIndexesAdmin(): RedirectResponse {
    $url = Url::fromRoute('view.jcc_roc_views.roc_rule_index_content_admin', [], ['absolute' => TRUE]);
    return new RedirectResponse($url->toString());
  }

}
