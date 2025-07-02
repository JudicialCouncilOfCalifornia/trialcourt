<?php

/**
 * @file
 * Preprocess and functions for alert content type and component.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\media\Entity\Media;
use Drupal\node\NodeInterface;
use Drupal\Core\Render\Markup;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_node(&$variables) {
  $node = $variables['node'];
  $function = "jcc_components_node_" . $node->bundle();
  if (function_exists($function)) {
    $function($variables, $node);
  }

  // Display content only when embed param is 1.  Hide header and footer.
  $variables['embed'] = \Drupal::request()->query->get('embed') == 1 ? TRUE : FALSE;

  if ($node->getType() == 'tentative_ruling') {
    $variables['judges_card'] = [];
    foreach ($node->field_judge as $judge_key => $judge_entity){
      $judge = $judge_entity->entity;
      $variables['judges_card'][$judge_key]['body'] = '';

      //rendering judge name field
      $variables['judges_card'][$judge_key]['title'] = $judge->getTitle();

      //rendering judge department field
      if ($judge->field_department){
        $judge_department = '';
        foreach($judge->field_department as $key => $department_term){
          $department_term_value = $department_term->get('entity')->getTarget()->getValue()->label();
          if($key == 0){
            $judge_department = $department_term_value;
          } else {
            $judge_department .= ', ' . $department_term_value;
          }
        }
        $variables['judges_card'][$judge_key]['department'] = $judge_department;
      }

      //rendering judge address field
      $judge_address = $judge->field_address;
      $judge_address_html = $judge_address->first()->address_line1 . ' ' . $judge_address->first()->address_line2;
      $judge_address_html .= '<br/>' . $judge_address->first()->locality . ', ' . $judge_address->first()->administrative_area . ' ' . $judge_address->first()->postal_code;
      $judge_address_html .= '<br/>' . $judge_address->first()->country_code;
      $variables['judges_card'][$judge_key]['body'] = Markup::create($judge_address_html);

      //rendering judge custom_link field
      if($judge->field_custom_link->first()){
        $variables['judges_card'][$judge_key]['link']['url'] = $judge->field_custom_link->first()->getUrl()->toString();
      }
    }
  }
  // Logic for location page variables.
  if ($node->getType() == 'location') {
    $variables['#attached']['library'][] = 'jcc_components/location-page';
    $current_path = \Drupal::request()->getRequestUri();
    if(\Drupal::service('path.matcher')->matchPath($current_path ,"/location/*")){
      build_service_markup($variables, $node);
     }
  }
}

/**
 * Node: Alert preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_components_node_alert(array &$variables, NodeInterface $node) {
  $variables['show_content'] = FALSE;
  // If should only show on front page, check if this is front page.
  if ($node->field_switch->first()->value) {
    if (\Drupal::service('path.matcher')->isFrontPage()) {
      $variables['show_content'] = TRUE;
      return;
    }
    else {
      return;
    }
  }
  // Check if we're on an allowed page.
  $current = \Drupal::routeMatch()->getParameter('node');
  if ($current instanceof NodeInterface) {
    $references = $node->field_node_reference->referencedEntities();
    // Not front page only, and no specific references, show everywhere.
    if (empty($references)) {
      $variables['show_content'] = TRUE;
      return;
    }
    // Otherwise check specific pages to show alert on.
    foreach ($references as $ref) {
      if ($current->id() == $ref->id()) {
        $variables['show_content'] = TRUE;
        return;
      }
    }
    // Also show if this is the alert's node view page.
    if ($current->id() == $node->id()) {
      $variables['show_content'] = TRUE;
    }
  }
}

/**
 * Implements hook_entity_build_defaults_alter().
 */
function jcc_custom_entity_build_defaults_alter(&$build, EntityInterface $entity, $view_mode) {
  switch ($entity->bundle()) {
    // Add cache context url.path to alert node content because we only want it
    // to render on specific page sometimes as controlled on the node itself.
    case 'alert':
      $build['#cache']['contexts'][] = 'url.path';
      break;

  }
}

/**
 * Node: Landing preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 */
function jcc_components_node_landing_page(array &$variables, NodeInterface $node) {
  // If home page check.
  $variables['is_front'] = \Drupal::service('path.matcher')->isFrontPage();

  // Breadcrumbs.
  $variables['breadcrumb'] = \Drupal::service('breadcrumb')
    ->build(\Drupal::routeMatch())
    ->toRenderable();

  // Populate menu if items are defined - second level only.
  $menu_items = jcc_get_menu_items('main', 1, 2);

  foreach ($menu_items as $content) {
    if ($content->inActiveTrail) {
      // If we want parent links for the current section.
      foreach ($content->subtree as $sub_content) {
        $variables['secondary_menu']['items'][] = [
          'title' => $sub_content->link->getTitle(),
          'url' => $sub_content->link->getUrlObject(),
          'in_active_trail' => $sub_content->inActiveTrail,
        ];
      }
    }
  }
}

/**
 * Node: Subpage preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 */
function jcc_components_node_subpage(array &$variables, NodeInterface $node) {
  $menu_link_manager = \Drupal::service('plugin.manager.menu.link');

  // Subpage node information.
  $current_path = \Drupal::service('path.current')->getPath();
  $current_path_alias = \Drupal::service('path_alias.manager')->getAliasByPath($current_path);
  preg_match('/^\/[^\/]*/', $current_path_alias, $parent_path);
  $current_section = $parent_path[0];

  // Populate the menu - second and third levels only.
  $menu_items = jcc_get_menu_items('main', 2, 3);
  $is_menu_item = $menu_link_manager->loadLinksByRoute('entity.node.canonical', ['node' => $node->nid->value]);

  // Return links only if subpage is within menu & menu depth.
  if ($menu_items && $is_menu_item) {
    foreach ($menu_items as $content) {
      // If we want links for primary active trail/parents of current section.
      $section = $menu_link_manager->createInstance($content->link->getParent())->getUrlObject()->toString();
      if ($section == $current_section) {
        $variables['secondary_menu']['items'][] = [
          'title' => $content->link->getTitle(),
          'url' => $content->link->getUrlObject(),
          'in_active_trail' => $content->inActiveTrail,
          'has_children' => $content->hasChildren,
        ];
      }

      // If we want links for the secondary active trail.
      if ($content->inActiveTrail) {
        foreach ($content->subtree as $sub_content) {
          // If we're in the third level, add parent for link to second level.
          if ($sub_content->inActiveTrail) {
            $variables['tertiary_menu']['parent'] = [
              'title' => $content->link->getTitle(),
              'url' => $content->link->getUrlObject(),
            ];
          }
          if (get_class($sub_content->link) != 'Drupal\Core\Menu\InaccessibleMenuLink') {
            $variables['tertiary_menu']['items'][] = [
              'title' => $sub_content->link->getTitle(),
              'url' => $sub_content->link->getUrlObject(),
              'in_active_trail' => $sub_content->inActiveTrail,
            ];
          }
        }
      }
    }
  }

  // Breadcrumbs get displayed on subpages.
  $variables['breadcrumb'] = \Drupal::service('breadcrumb')
    ->build(\Drupal::routeMatch())
    ->toRenderable();
}

/**
 * Node: location preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node object.
 */
function jcc_components_node_location(array &$variables, NodeInterface $node) {
  // Format the hours content in a format the PL template can receive.
  if (!empty($node->field_components)) {
    $components = $node->field_components->referencedEntities();
    foreach ($components as $component) {
      $label = '';
      if (!empty($component->field_heading) && !$component->field_heading->isEmpty()) {
        $label = $component->field_heading->first()->view();
      }
      $details = '';
      if (!empty($component->field_hours)) {
        $details = $component->field_hours->view('default');
      }
      $variables['hours'][] = [
        'label' => $label,
        'details' => $details,
      ];
    }
  }
  // Format the address for the directions link.
  if (!empty($node->field_address)) {
    $address = $node->field_address->view();
    // No label; no country.
    $address['#label_display'] = 'hidden';
    $address[0]['country']['#value'] = '';
    $address = render($address);
    $address = trim(strip_tags($address));
    $address = str_replace(PHP_EOL, '<br />', $address);
    $variables['processed_address'] = [
      '#type' => 'processed_text',
      '#text' => $address,
      '#format' => 'basic_html',
    ];
  }
  // Set a unique location id.
  // Exclude special characters and convert to lowercase.
  $node_title = preg_replace('/[^A-Za-z0-9\-]/', ' ', strtolower($node->getTitle()));
  // Get the first two words joined by hyphen.
  $node_title = implode('-', array_slice(array_filter(explode(' ', $node_title)), 0, 2));
  // Set location_id variable to be consumed by twig.
  $variables['location_id'] = $node_title . '-' . $node->id();
}

/**
 * Node: News preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_components_node_news(array &$variables, NodeInterface $node) {
  $variables['breadcrumb'] = \Drupal::service('breadcrumb')
    ->build(\Drupal::routeMatch())
    ->toRenderable();
}

/**
 * Node: Event preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_components_node_event(array &$variables, NodeInterface $node) {
  $variables['breadcrumb'] = \Drupal::service('breadcrumb')
    ->build(\Drupal::routeMatch())
    ->toRenderable();

  // Ensure date range field timezone.
  $variables['startDate'] = $node->field_date_range->start_date;
  $variables['endDate'] = $node->field_date_range->end_date;
}

/**
 * Node: Document preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_components_node_document(array &$variables, NodeInterface $node) {
  // Contextual edit media file option.
  if (isset($node->field_media->target_id)) {
    $media = Media::load($node->field_media->target_id);

    $variables['edit_option'] = empty($media) ? NULL : _jcc_components_can_edit_entity($media, t('Edit'));
  }

  // Ensure date range field timezone.
  if (isset($node->field_date_range->start_date)) {
    $variables['startDate'] = $node->field_date_range->start_date;
    $variables['endDate'] = $node->field_date_range->end_date;
  }
}

/**
 * Get menu items to build navigation onto nodes.
 *
 * @param string $menu_name
 *   The menu structure name.
 * @param int $min_depth
 *   The starting level to retrieve items.
 * @param int $max_depth
 *   The deepest level to retrieve items.
 *
 * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
 *   The menu items.
 */
function jcc_get_menu_items($menu_name, $min_depth, $max_depth) {
  $menu_name = $menu_name;
  $parameters = new MenuTreeParameters();
  $parameters->onlyEnabledLinks();
  $parameters->setMinDepth($min_depth);
  $parameters->setMaxDepth($max_depth);
  $menu_active_trail = \Drupal::service('menu.active_trail')
    ->getActiveTrailIds($menu_name);
  $parameters->setActiveTrail($menu_active_trail);
  // Load the tree.
  $tree = \Drupal::menuTree()->load($menu_name, $parameters);
  $manipulators = [
    ['callable' => 'menu.default_tree_manipulators:checkAccess'],
    ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
  ];
  $tree = \Drupal::menuTree()->transform($tree, $manipulators);

  return $tree;
}
