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

  /**
   * Redirect from base request admin route to view the admin display.
   */
  public function redirectToTaxonomyRfpSolicitationRequestTopicList(): RedirectResponse {
    $route_provider = \Drupal::service('router.route_provider');
    $exists = count($route_provider->getRoutesByNames(['entity.taxonomy_vocabulary.overview_form'])) === 1;
    if ($exists) {
      $url = Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => 'rfp_solicitation_topics'], ['absolute' => TRUE]);
      return new RedirectResponse($url->toString());
    }

    return new RedirectResponse('/');
  }

  /**
   * Redirect from base request admin route to view the admin display.
   */
  public function redirectToTaxonomyRfpSolicitationRequestDeptGroupOrgList(): RedirectResponse {
    $route_provider = \Drupal::service('router.route_provider');
    $exists = count($route_provider->getRoutesByNames(['entity.taxonomy_vocabulary.overview_form'])) === 1;
    if ($exists) {
      $url = Url::fromRoute('entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => 'rfp_solicitation_dept_group_org'], ['absolute' => TRUE]);
      return new RedirectResponse($url->toString());
    }

    return new RedirectResponse('/');
  }

}
