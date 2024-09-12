<?php

/**
 * @file
 * The jcc_elevated_sections include file for preprocess functionality.
 */

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\jcc_elevated_sections\Constants\JccSectionConstants;

/**
 * Implements hook_preprocess_page_title().
 */
function jcc_elevated_sections_preprocess_page_title(&$variables) {
  // Set some variables.
  $section_service = Drupal::service('jcc_elevated_sections.service');
  $vocab_source = $section_service->getSectionSourceId();
  $edit_form = 'entity.taxonomy_term.edit_form';
  $add_form = 'entity.taxonomy_term.add_form';
  $route_match = \Drupal::routeMatch();
  $route = $route_match->getRouteName();

  // If we are on the taxonomy edit form.
  if ($route == $edit_form) {
    if ($vocab = $route_match->getParameter('taxonomy_vocabulary')) {
      // If editing a term in source vocabulary, add the vocab name to title.
      $variables['title'] = $vocab->id() == $vocab_source ?
        t('Edit @bundle', ['@bundle' => $vocab->label()]) :
        $variables['title'];
    }
  }

  // If we are on the taxonomy add form.
  if ($route == $add_form) {
    if ($vocab = $route_match->getParameter('taxonomy_vocabulary')) {
      // If adding a term to the source vocabulary, add the vocab name to title.
      $variables['title'] = $vocab->id() == $vocab_source ?
        t('Edit @bundle', ['@bundle' => $vocab->label()]) :
        $variables['title'];
    }
  }

}

/**
 * Implements hook_preprocess_preprocess_html().
 */
function jcc_elevated_sections_preprocess_html(&$variables) {
  // Set some variables.
  $section_service = Drupal::service('jcc_elevated_sections.service');
  $vocab_source = $section_service->getSectionSourceId();
  $edit_form = 'entity.taxonomy_term.edit_form';
  $add_form = 'entity.taxonomy_term.add_form';
  $route_match = \Drupal::routeMatch();
  $route = $route_match->getRouteName();

  // If we are on the taxonomy edit form.
  if ($route == $edit_form) {
    if ($vocab = $route_match->getParameter('taxonomy_vocabulary')) {
      // If editing a term in source vocabulary, add the vocab name to title.
      $variables['head_title']['title'] = $vocab->id() == $vocab_source ?
        t('Edit @bundle', ['@bundle' => $vocab->label()]) :
        $variables['head_title']['title'];
    }
  }

  // If we are on the taxonomy add form.
  if ($route == $add_form) {
    if ($vocab = $route_match->getParameter('taxonomy_vocabulary')) {
      // If adding a term to the source vocabulary, add the vocab name to title.
      $variables['head_title']['title'] = $vocab->id() == $vocab_source ?
        t('Edit @bundle', ['@bundle' => $vocab->label()]) :
        $variables['head_title']['title'];
    }
  }

  // If the node is a sectioned node, set the page html title to follow the
  // pattern of "page-title | section-name | site-name".
  if ($node = $route_match->getParameter('node')) {
    $sid = $section_service->getSectionForNode($node);
    $section = $section_service->getSectionInfo($sid);
    if ($section && $section->label()) {
      $section_homepage_nid = $section->get('jcc_section_homepage')->target_id;
      $current_nid = $node->id();
      // We only need to apply this to section pages that are not the section
      // homepage.
      if ($current_nid != $section_homepage_nid) {
        $site_name = \Drupal::config('system.site')->get('name');
        $variables['head_title']['title'] = $node->getTitle() . ' | ' . $section->label() . ' | ' . $site_name;
      }
    }
  }
}

/**
 * Implements hook_preprocess_page().
 */
function jcc_elevated_sections_preprocess_page(array &$variables) {
  $section_service = Drupal::service('jcc_elevated_sections.service');
  $node_manager = \Drupal::entityTypeManager()->getStorage('node');
  $route_match = \Drupal::routeMatch();
  $route = $route_match->getRouteObject();
  $is_admin = \Drupal::service('router.admin_context')->isAdminRoute($route);
  $variables['section_navigation'] = FALSE;

  // If we are on a node, and not on the admin side, and the node is of a type
  // that can be sectioned.
  if (!$is_admin && $node = $route_match->getParameter('node')) {
    if ($section_service->isNodeSectionable($node->bundle())) {

      // We need to get the homepage source of the section for the given node.
      $sid = $section_service->getSectionForNode($node);
      $section = $section_service->getSectionInfo($sid);

      if ($section) {
        $section_homepage_nid = $section->getSectionHomepage();

        // Check if there is a section homepage id.
        if (is_numeric($section_homepage_nid)) {
          $section_homepage = $node_manager->load($section_homepage_nid);

          // Build out the relevant menu tree. This depends on the current page
          // being in a menu and only on subpages.
          $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
          $section_links = $menu_link_manager->loadLinksByRoute('entity.node.canonical', ['node' => $section_homepage_nid]);

          if (!empty($section_links)) {
            $menu_id = array_key_first($section_links);
            $params = new MenuTreeParameters();
            $params->setRoot($menu_id);
            $params->setActiveTrail([
              $menu_id,
              reset($section_links)->getPluginId(),
            ]);

            $menu_tree = \Drupal::menuTree();
            $tree = $menu_tree->load($menu_id, $params);
            $tree = $menu_tree->transform($tree, [
              ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
              ['callable' => 'menu.default_tree_manipulators:checkAccess'],
              ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
            ]);

            $menu_build = $menu_tree->build($tree);

            // If we have a menu that can be built.
            if (!empty($menu_build['#items'])) {

              // Set cacheTags on the page to help with menu builds.
              $variables['page']['#cache']['contexts'][] = 'url.path';
              $variables['page']['#cache']['contexts'][] = 'user';

              // Add menu changes to the tag list.
              $variables['page']['#cache']['tags'][] = 'menu_link_content_list';
              $variables['page']['#cache']['tags'][] = 'config:system.menu.' . $menu_id;
              $variables['page']['#cache']['tags'][] = 'menu:' . $menu_id;

              // Add section taxonomy term to tag list.
              $variables['page']['#cache']['tags'][] = 'taxonomy_term_list:' . JccSectionConstants::JCC_SECTIONS_DEFAULT_SOURCE_ID;

              // Set the section node and its type to the tag list.
              $variables['page']['#cache']['tags'][] = 'node:' . $node->id();
              foreach ($section_service->getSectionableTypes() as $name) {
                if ($name) {
                  $variables['page']['#cache']['tags'][] = 'node_list:' . $name;
                }
              }

              // Create our storybook component friendly sidebar navigation.
              if (!empty($menu_build['#items'][$menu_id]['below'])) {

                // We render the sublinks of the node/menu item designated as
                // the section homepage.
                $links = $menu_build['#items'][$menu_id]['below'];

                // Set caches to trigger on landing and subpage changes.
                $variables['section_navigation'] = [
                  'section_heading' => $section->label(),
                  'links' => jcc_elevated_sections_build_menu_tree($links, $section_homepage, $section),
                  'mobile_links' => jcc_elevated_sections_build_menu_tree($links, $section_homepage, $section, TRUE),
                ];
              }
            }
          }
        }
      }
    }
  }

}
