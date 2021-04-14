<?php

namespace Drupal\jcc_messaging_center\Controller;

use Drupal\views\Controller\ViewAjaxController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class JCCMessagingCenterAjaxController.
 */
class JccMessagingCenterAjaxController extends ViewAjaxController {

  /**
   * Loads group management view.
   */
  public function ajaxView(Request $request) {
    $view_name = 'user_groups_management';
    $display_id = 'page_1';
    $dom_id = "jcc_messaging_center_users_in_group";

    $request->request->set('view_name', $view_name);
    $request->request->set('view_display_id', $display_id);
    $request->request->set('view_args', $request->get('group'));
    $request->request->set('view_dom_id', $dom_id);

    return parent::ajaxView($request);
  }

}
