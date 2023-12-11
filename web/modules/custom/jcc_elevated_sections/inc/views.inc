<?php

/**
 * @file
 * The jcc_elevated_sections include file for view alter functionality.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_query_alter().
 */
function jcc_elevated_sections_views_query_alter(ViewExecutable $view, QueryPluginBase $query) {
  // Set some variables.
  $name = $view->id();
  $display = $view->current_display;
  $view_name_display = $name . ':' . $display;
  $section_service = Drupal::service('jcc_elevated_sections.service');
  $route_match = \Drupal::routeMatch();
  $route = $route_match->getRouteObject();
  $is_admin = \Drupal::service('router.admin_context')->isAdminRoute($route);

  // If we have a view that is assigned to be contextually filtered.
  if (!$is_admin && $section_service->isViewSectionable($view_name_display)) {

    // Figure out the section for the current node.
    $sid = jcc_get_current_page_section();
    $general_content_excluded = $section_service->isViewGeneralContentExcluded($view_name_display);
    if ($sid && $general_content_excluded) {
      $query->addWhere('', 'node_field_data.jcc_section', [$sid], 'IN');
    }
  }
}

/**
 * Implements hook_views_pre_view().
 */
function jcc_elevated_sections_views_pre_render(ViewExecutable $view) {
  // Set some variables.
  $name = $view->id();
  $display = $view->current_display;
  $view_name_display = $name . ':' . $display;
  $section_service = Drupal::service('jcc_elevated_sections.service');
  $route_match = \Drupal::routeMatch();
  $route = $route_match->getRouteObject();
  $is_admin = \Drupal::service('router.admin_context')->isAdminRoute($route);
  $exposed_form_overridden_to_contextual = $section_service->isViewExposedFormContextualOverride($view_name_display);

  // If we have a view that is assigned to be contextually filtered.
  if (!$is_admin && $section_service->isViewSectionable($view_name_display)) {

    // Grab the filter options from the view and set a new one.
    $filters = $view->display_handler->getOption('filters');

    // Determine if the view has an exposed form. If it has an exposed form, we
    // do not want to do any contextual filtering. The exposed form will handle
    // that functionality.
    $base_table = array_key_first($view->getBaseTables());
    $jcc_section_filter_name = $base_table == 'users_field_data' ? 'jcc_sections_target_id' : 'jcc_section';
    $view_contains_exposed_filter = FALSE;
    foreach ($filters as $filter) {
      // If the only filter applied is the code based jcc filter, ignore it.
      if ($filter['id'] == $jcc_section_filter_name) {
        continue;
      }
      // Look at the other filters and see if any of them are set to exposed.
      if (isset($filter['exposed']) && $filter['exposed'] && !$exposed_form_overridden_to_contextual) {
        $view_contains_exposed_filter = TRUE;
      }
    }

    // Figure out the section for the current node.
    $sid = jcc_get_current_page_section();

    if ($sid && !$view_contains_exposed_filter && !empty($view->result) && !$exposed_form_overridden_to_contextual) {
      $results = $view->result;
      $filteredResults = [];
      $index_key = 0;

      // Here is where we need to set the option to make a view general content
      // inclusive (TRUE) or exclusive (FALSE). Inclusive means display content
      // that is not sectioned, but still matches the other criteria. Exclusive
      // means only display content that has a section set and matching.
      $general_content_excluded = $section_service->isViewGeneralContentExcluded($view_name_display);

      foreach ($results as $result) {

        // Find the section for each resulting entity, and if the value is
        // empty, then it is most likely general/non-sectioned content. In that
        // case we don't remove it (we just add it back to the list of items).
        // If the result entity does return a section ID, and it matches the
        // current Section ID, we add it back to the result list. This setup
        // removes Sectioned content that does not match current section
        // context.
        $result_section = $result->_entity->jcc_section->getValue();

        // If "display general content" along with section contextual content is
        // set to true.
        if (!$general_content_excluded) {
          if (empty($result_section)) {
            $result->index = $index_key;
            $filteredResults[] = $result;
            $index_key += 1;
          }
          elseif (isset($result_section[0]) && $result_section[0]['target_id'] == $sid) {
            $result->index = $index_key;
            $filteredResults[] = $result;
            $index_key += 1;
          }
        }
        else {
          // If this view should only display contextual sectioned items.
          if (isset($result_section[0]) && $result_section[0]['target_id'] == $sid) {
            $result->index = $index_key;
            $filteredResults[] = $result;
            $index_key += 1;
          }
        }
      }

      $count = count($filteredResults);
      $view->result = $filteredResults;
      $view->total_rows = $count;
      $view->getPager()->total_items = $count;
      $view->getPager()->updatePageInfo();
    }

    if (!$sid && !$view_contains_exposed_filter) {
      $results = $view->result;
      $filteredResults = [];
      $index_key = 0;

      foreach ($results as $result) {
        // Find the section for each resulting entity, and if the value is
        // empty, then it is most likely general/non-sectioned content. In that
        // case we don't remove it (we just add it back to the list of items).
        // If the result entity does return a section ID. This setup
        // removes Sectioned content from general content views.
        $result_section = $result->_entity->jcc_section->getValue();
        if (empty($result_section)) {
          $result->index = $index_key;
          $filteredResults[] = $result;
          $index_key += 1;
        }
      }

      $count = count($filteredResults);
      $view->result = $filteredResults;
      $view->total_rows = $count;
      $view->getPager()->total_items = $count;
      $view->getPager()->updatePageInfo();
    }
  }

}

/**
 * Implements hook_views_pre_view().
 */
function jcc_elevated_sections_views_pre_view(ViewExecutable $view, $display_id, array &$args) {
  // Set some variables.
  $name = $view->id();
  $display = $view->current_display;
  $view_name_display = $name . ':' . $display;
  $section_service = Drupal::service('jcc_elevated_sections.service');
  $route_match = \Drupal::routeMatch();
  $route = $route_match->getRouteObject();
  $is_admin = \Drupal::service('router.admin_context')->isAdminRoute($route);
  $exposed_form_overridden_to_contextual = $section_service->isViewExposedFormContextualOverride($view_name_display);

  // If we have a view that is assigned to be contextually filtered.
  if ($section_service->isViewSectionable($view_name_display)) {

    $route = \Drupal::routeMatch()->getRouteObject();
    $is_admin = \Drupal::service('router.admin_context')->isAdminRoute($route);

    $base_table = array_key_first($view->getBaseTables());
    $jcc_section_filter_name = $base_table == 'users_field_data' ? 'jcc_sections_target_id' : 'jcc_section';

    // Grab the filter options from the view and set a new one.
    $filters = $view->display_handler->getOption('filters');

    $enable_form_filter = FALSE;
    foreach ($filters as $filter) {
      if ($filter['id'] == $jcc_section_filter_name) {
        continue;
      }

      if (isset($filter['exposed']) && $filter['exposed'] && !$exposed_form_overridden_to_contextual) {
        $enable_form_filter = TRUE;
      }
    }

    // Build out our default filter for our custom "jcc_section".
    if ($enable_form_filter) {
      $filters[$jcc_section_filter_name] = _jcc_elevated_sections_default_view_filter_info($base_table, $is_admin);
    }

    $view->display_handler->overrideOption('filters', $filters);
  }
}

/**
 * Implements hook_form_views_exposed_form_alter().
 */
function jcc_elevated_sections_form_views_exposed_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // Set some variables.
  $view = $form_state->get('view');
  $name = $view->id();
  $display = $view->current_display;
  $view_name_display = $name . ':' . $display;
  $section_service = Drupal::service('jcc_elevated_sections.service');

  $base_table = array_key_first($view->getBaseTables());
  $jcc_section_filter_name = $base_table == 'users_field_data' ? 'jcc_sections_target_id' : 'jcc_section';

  // Check if form has info items, which means it has an exposed form from view.
  // This is the way we check that Sectionable views with existing exposed forms
  // receive the JCC Sections exposed form element. We add 'filter-jcc_section'
  // in a previous hook for reasons, but if it is the only item in the info, we
  // need to remove it. This way we will know that originally this view does not
  // have a form.
  $info = $form['#info'];
  unset($info['filter-' . $jcc_section_filter_name]);
  $has_exposed_form = (bool) !empty($info);

  // Add the section filtering if the view is assigned to have a filter (and not
  // overridden to be contextual).
  if ($has_exposed_form &&
    $section_service->isViewSectionable($view_name_display) &&
    !$section_service->isViewExposedFormContextualOverride($view_name_display)) {

    $form['captcha']['#access'] = FALSE;

    $route = \Drupal::routeMatch()->getRouteObject();
    $is_admin = \Drupal::service('router.admin_context')->isAdminRoute($route);
    $options[''] = $is_admin ? t('- Any -') : t('- Section -');

    if (!empty($section_service->getSections())) {

      foreach ($section_service->getSections() as $term) {
        $options[$term->id()] = $term->label();
      }

      $form['#info']['filter-' . $jcc_section_filter_name] = [
        'operator' => $jcc_section_filter_name . '_op',
        'value' => $jcc_section_filter_name,
        'label' => $is_admin ? 'Section' : '',
        'description' => '',
      ];

      $form[$jcc_section_filter_name] = [
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => '',
      ];

      $form['captcha']['#access'] = FALSE;

      unset($form[$jcc_section_filter_name]['#options']['All']);
    }
  }

  //
  // Alter the news view.
  $current_action_path = $form['#action'];
  $current_path = \Drupal::service('path.current')->getPath();
  $current_alias_path = \Drupal::service('path_alias.manager')->getAliasByPath($current_path);
  if ($current_action_path == '/views/news') {
    $form['#action'] = $current_action_path != $current_alias_path ? $current_alias_path : $current_action_path;
  }

}

/**
 * Return default filter info data, needed as base for building new filters.
 */
function _jcc_elevated_sections_default_view_filter_info($table, $display_label = TRUE, $enable_form_filter = FALSE): array {

  // Build list of roles for the default "remember roles" spot.
  $role_list = [];
  $roles = Drupal::entityTypeManager()
    ->getStorage('user_role')
    ->loadMultiple();

  if (!empty($roles)) {
    foreach ($roles as $role) {
      $role_list[$role->id()] = $role->id() == 'authenticated' ? 'authenticated' : 0;
    }
  }

  switch ($table) {
    case 'users_field_data':
      // Use a diff table for Users, but it starts from users_field_data.
      $table = 'user__jcc_sections';
      $field_name = 'jcc_sections_target_id';
      $type = 'user';
      $entity_field = 'jcc_sections';
      break;

    case 'media_field_data':
      $table = 'media_field_data';
      $field_name = 'jcc_section';
      $type = 'media';
      $entity_field = 'jcc_section';
      break;

    default:
      $table = 'node_field_data';
      $field_name = 'jcc_section';
      $type = 'node';
      $entity_field = 'jcc_section';
      break;
  }

  $label = $display_label ? 'Section' : '';

  return [
    'id' => $field_name,
    'table' => $table,
    'field' => $field_name,
    'relationship' => 'none',
    'group_type' => 'group',
    'admin_label' => '',
    'entity_type' => $type,
    'entity_field' => $entity_field,
    'plugin_id' => 'numeric',
    'operator' => '=',
    'value' => [
      'min' => '',
      'max' => '',
      'value' => '',
    ],
    'group' => 1,
    'exposed' => TRUE,
    'expose' => [
      'operator_id' => $field_name . '_op',
      'label' => $label,
      'description' => '',
      'use_operator' => FALSE,
      'operator' => $field_name . '_op',
      'operator_limit_selection' => FALSE,
      'operator_list' => [],
      'identifier' => $field_name,
      'required' => FALSE,
      'remember' => FALSE,
      'multiple' => FALSE,
      'remember_roles' => $role_list,
      'min_placeholder' => '',
      'max_placeholder' => '',
      'placeholder' => '',
    ],
    'is_grouped' => FALSE,
    'group_info' => [
      'label' => '',
      'description' => '',
      'identifier' => '',
      'optional' => TRUE,
      'widget' => 'select',
      'multiple' => FALSE,
      'remember' => FALSE,
      'default_group' => 'All',
      'default_group_multiple' => [],
      'group_items' => [],
    ],
  ];
}
