<?php

namespace Drupal\jcc_roc\Controller;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for Rules of Court.
 */
class JccRocController {

  /**
   * Redirect from base rule admin route to view document admin display.
   */
  public function redirectToRuleDocumentAdmin(): RedirectResponse {
    $url = Url::fromRoute('view.jcc_roc_views.document_content_admin', [], ['absolute' => TRUE]);
    return new RedirectResponse($url->toString());
  }

}
